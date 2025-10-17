<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\WidgetAudit;
use App\Models\WidgetDefinition;
use App\Support\WidgetDefaults;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WidgetController extends Controller
{
    public function catalog(Request $request): JsonResponse
    {
        $this->ensureDefaultDefinitions();

        $widgets = WidgetDefinition::query()
            ->orderBy('name')
            ->get()
            ->map(fn (WidgetDefinition $definition) => [
                'id' => $definition->id,
                'slug' => $definition->slug,
                'name' => $definition->name,
                'kind' => $definition->kind,
                'description' => $definition->description,
                'source_dataset' => $definition->source_dataset,
                'default_config' => $definition->default_config,
            ])
            ->values();

        return response()->json(['widgets' => $widgets]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $this->ensureDefaultDefinitions();

        $dashboard = $this->resolveDashboard($request);

        $widgets = $dashboard->widgets()
            ->with('definition')
            ->orderBy('position_index')
            ->get()
            ->map(fn (DashboardWidget $widget) => $this->serialiseWidget($widget))
            ->values();

        return response()->json([
            'dashboard_id' => $dashboard->id,
            'title' => $dashboard->title,
            'widgets' => $widgets,
        ]);
    }

    public function attach(Request $request): JsonResponse
    {
        $this->ensureDefaultDefinitions();

        $dashboard = $this->resolveDashboard($request);

        $data = $request->validate([
            'slug' => [
                'required',
                'string',
                Rule::exists('widget_definitions', 'slug'),
            ],
            'position_index' => ['nullable', 'integer', 'min:0'],
            'visual_config' => ['nullable', 'array'],
            'data_filters' => ['nullable', 'array'],
            'layout' => ['nullable', 'array'],
        ]);

        $definition = WidgetDefinition::where('slug', $data['slug'])->firstOrFail();

        $position = $data['position_index'] ?? $this->nextPositionIndex($dashboard);

        $widget = DB::transaction(function () use ($dashboard, $definition, $data, $position) {
            $widget = new DashboardWidget([
                'widget_definition_id' => $definition->id,
                'position_index' => $position,
                'visual_config' => $data['visual_config'] ?? $definition->default_config ?? [],
                'data_filters' => $data['data_filters'] ?? [],
                'layout' => $data['layout'] ?? null,
            ]);

            $dashboard->widgets()->save($widget);

            $this->recordAudit($widget, 'created', [
                'definition' => $definition->only(['id', 'slug', 'name', 'kind']),
                'position_index' => $widget->position_index,
            ]);

            return $widget->fresh(['definition']);
        });

        return response()->json([
            'widget' => $this->serialiseWidget($widget),
        ], 201);
    }

    public function update(Request $request, DashboardWidget $widget): JsonResponse
    {
        $this->authoriseWidget($widget);

        $data = $request->validate([
            'position_index' => ['nullable', 'integer', 'min:0'],
            'visual_config' => ['nullable', 'array'],
            'data_filters' => ['nullable', 'array'],
            'layout' => ['nullable', 'array'],
        ]);

        $original = $widget->only(['position_index', 'visual_config', 'data_filters', 'layout']);
        $action = 'updated';

        DB::transaction(function () use ($widget, $data) {
            if (array_key_exists('position_index', $data) && $data['position_index'] !== null) {
                $this->reorderWidget($widget, (int) $data['position_index']);
                unset($data['position_index']);
            }

            $attributes = [];

            foreach (['visual_config', 'data_filters', 'layout'] as $attribute) {
                if (array_key_exists($attribute, $data)) {
                    $attributes[$attribute] = $data[$attribute];
                }
            }

            if ($attributes !== []) {
                $widget->fill($attributes);
                $widget->save();
            }

            $widget->refresh('definition');
        });

        $changes = [
            'before' => $original,
            'after' => $widget->only(['position_index', 'visual_config', 'data_filters', 'layout']),
        ];

        if ($changes['before']['position_index'] !== $changes['after']['position_index']) {
            $action = 'reordered';
        } elseif ($changes['before']['data_filters'] !== $changes['after']['data_filters']) {
            $action = 'filters_changed';
        }

        if ($changes['before'] !== $changes['after']) {
            $this->recordAudit($widget, $action, $changes);
        }

        return response()->json([
            'widget' => $this->serialiseWidget($widget),
        ]);
    }

    public function destroy(Request $request, DashboardWidget $widget): JsonResponse
    {
        $this->authoriseWidget($widget);

        DB::transaction(function () use ($widget) {
            $payload = $this->serialiseWidget($widget);
            $this->recordAudit($widget, 'removed', ['widget' => $payload]);
            $widget->delete();
        });

        return response()->json(null, 204);
    }

    protected function resolveDashboard(Request $request): Dashboard
    {
        $user = $request->user();

        return Dashboard::firstOrCreate(
            ['user_id' => $user->id, 'title' => 'Panel principal'],
            ['layout_settings' => null]
        );
    }

    protected function nextPositionIndex(Dashboard $dashboard): int
    {
        $max = $dashboard->widgets()->max('position_index');

        return is_null($max) ? 0 : $max + 1;
    }

    protected function reorderWidget(DashboardWidget $widget, int $newPosition): void
    {
        $dashboardId = $widget->dashboard_id;

        $siblings = DashboardWidget::query()
            ->where('dashboard_id', $dashboardId)
            ->orderBy('position_index')
            ->get();

        $siblings = $siblings
            ->reject(fn (DashboardWidget $item) => $item->id === $widget->id)
            ->values();

        $boundedPosition = max(0, min($newPosition, $siblings->count()));
        $siblings->splice($boundedPosition, 0, [$widget]);

        foreach ($siblings as $index => $item) {
            if ($item->position_index !== $index) {
                DashboardWidget::whereKey($item->id)->update(['position_index' => $index]);
            }
            if ($item->id === $widget->id) {
                $widget->position_index = $index;
            }
        }
    }

    protected function serialiseWidget(DashboardWidget $widget): array
    {
        $definition = $widget->definition;

        return [
            'id' => $widget->id,
            'dashboard_id' => $widget->dashboard_id,
            'widget_definition_id' => $widget->widget_definition_id,
            'slug' => $definition?->slug,
            'kind' => $definition?->kind,
            'title' => $widget->visual_config['title'] ?? $definition?->name ?? $definition?->slug,
            'data_filters' => $widget->data_filters ?? [],
            'visual_config' => $widget->visual_config ?? [],
            'layout' => $widget->layout ?? null,
            'position_index' => $widget->position_index,
            'widget_definition' => $definition ? [
                'id' => $definition->id,
                'slug' => $definition->slug,
                'name' => $definition->name,
                'kind' => $definition->kind,
                'description' => $definition->description,
                'source_dataset' => $definition->source_dataset,
                'default_config' => $definition->default_config,
            ] : null,
        ];
    }

    protected function authoriseWidget(DashboardWidget $widget): void
    {
        $userId = Auth::id();

        if (!$userId || $widget->dashboard?->user_id !== $userId) {
            abort(403, 'No puedes modificar este widget.');
        }
    }

    protected function recordAudit(DashboardWidget $widget, string $action, array $payload = []): void
    {
        WidgetAudit::create([
            'dashboard_widget_id' => $widget->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'payload' => $payload,
        ]);
    }

    /**
     * Ensure the widget catalog is populated with the default definitions.
     */
    protected function ensureDefaultDefinitions(): void
    {
        $defaults = WidgetDefaults::catalog();
        if ($defaults === []) {
            return;
        }

        $now = now();
        $rows = array_map(function (array $definition) use ($now) {
            return [
                'slug' => $definition['slug'],
                'name' => $definition['name'],
                'kind' => $definition['kind'],
                'description' => $definition['description'] ?? null,
                'source_dataset' => $definition['source_dataset'] ?? null,
                'default_config' => isset($definition['default_config'])
                    ? json_encode($definition['default_config'])
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $defaults);

        WidgetDefinition::query()->upsert(
            $rows,
            ['slug'],
            ['name', 'kind', 'description', 'source_dataset', 'default_config', 'updated_at']
        );
    }
}
