<?php

namespace App\Http\Controllers;

use App\Models\KpiAlert;
use App\Models\KpiAlertEvent;
use App\Services\Kpi\KpiAlertEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KpiAlertController extends Controller
{
    public function __construct(private readonly KpiAlertEvaluator $evaluator)
    {
    }

    public function definitions(): JsonResponse
    {
        $definitions = collect(config('kpi_alerts.definitions', []))
            ->map(function (array $definition, string $slug) {
                return [
                    'slug' => $slug,
                    'name' => $definition['name'] ?? $slug,
                    'description' => $definition['description'] ?? '',
                    'unit' => $definition['unit'] ?? '',
                    'supports_site_selection' => (bool) ($definition['supports_site_selection'] ?? false),
                    'default_operator' => $definition['default_operator'] ?? 'above',
                    'default_threshold' => $definition['default_threshold'] ?? 0,
                ];
            })
            ->values();

        return response()->json(['definitions' => $definitions]);
    }

    public function index(Request $request): JsonResponse
    {
        $alerts = $this->alertsQuery($request)
            ->latest()
            ->get()
            ->map(fn (KpiAlert $alert) => $this->serialiseAlert($alert));

        return response()->json(['alerts' => $alerts]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateAlertPayload($request);

        $alert = $request->user()->kpiAlerts()->create($data);

        return response()->json([
            'alert' => $this->serialiseAlert($alert),
        ], 201);
    }

    public function update(Request $request, KpiAlert $kpiAlert): JsonResponse
    {
        $this->authoriseAlert($request, $kpiAlert);

        $data = $this->validateAlertPayload($request, $kpiAlert, true);
        $kpiAlert->fill($data);
        $kpiAlert->save();

        return response()->json([
            'alert' => $this->serialiseAlert($kpiAlert),
        ]);
    }

    public function destroy(Request $request, KpiAlert $kpiAlert): JsonResponse
    {
        $this->authoriseAlert($request, $kpiAlert);
        $kpiAlert->delete();

        return response()->json(null, 204);
    }

    public function events(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->evaluator->evaluate($user);

        $query = $this->eventsQuery($request);
        $siteFilter = $request->input('site_id');
        if ($user->isSuperAdmin() && $siteFilter !== null && $siteFilter !== '') {
            $query->whereHas('alert', function ($builder) use ($siteFilter) {
                $builder->where('site_id', $siteFilter);
            });
        }

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }
        $limit = $request->integer('limit', 50);
        if ($limit > 0) {
            $query->limit($limit);
        }
        $events = $query->get()->map(fn (KpiAlertEvent $event) => $this->serialiseEvent($event));

        return response()->json(['events' => $events]);
    }

    public function markEvent(Request $request, KpiAlertEvent $event): JsonResponse
    {
        if ($event->user_id !== $request->user()->id) {
            abort(403);
        }

        $event->markAsRead();

        return response()->json([
            'event' => $this->serialiseEvent($event->fresh('alert')),
        ]);
    }

    public function markAllEvents(Request $request): JsonResponse
    {
        $count = $request->user()
            ->kpiAlertEvents()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['updated' => $count]);
    }

    protected function validateAlertPayload(Request $request, ?KpiAlert $alert = null, bool $partial = false): array
    {
        $definitions = config('kpi_alerts.definitions', []);
        $slugRules = [
            Rule::requiredIf(!$partial),
            Rule::in(array_keys($definitions)),
        ];

        $data = $request->validate([
            'kpi_slug' => $slugRules,
            'comparison_operator' => [
                $partial ? 'sometimes' : 'required',
                Rule::in(['above', 'below']),
            ],
            'threshold_value' => [$partial ? 'sometimes' : 'required', 'numeric'],
            'site_id' => ['nullable', 'string', 'max:64'],
            'is_active' => ['sometimes', 'boolean'],
            'cooldown_minutes' => ['sometimes', 'integer', 'between:1,1440'],
        ]);

        $definition = $definitions[$data['kpi_slug'] ?? $alert?->kpi_slug] ?? null;
        if (!$definition) {
            abort(422, 'La métrica seleccionada no está disponible.');
        }

        if ($request->user()->isSuperAdmin()) {
            if ($definition['supports_site_selection'] ?? false) {
                $siteId = $data['site_id'] ?? ($partial ? $alert?->site_id : null);
                if (!$siteId) {
                    abort(422, 'Debes seleccionar un sitio para esta alerta.');
                }
                $data['site_id'] = (string) $siteId;
            } else {
                $data['site_id'] = null;
            }
        } else {
            $siteId = $request->user()->siteId();
            if (!$siteId) {
                abort(403, 'El usuario no tiene un sitio asignado.');
            }
            $data['site_id'] = (string) $siteId;
        }

        if (!$partial && !array_key_exists('cooldown_minutes', $data)) {
            $data['cooldown_minutes'] = config('kpi_alerts.default_cooldown_minutes', 30);
        }

        return $data;
    }

    protected function serialiseAlert(KpiAlert $alert): array
    {
        return [
            'id' => $alert->id,
            'kpi_slug' => $alert->kpi_slug,
            'comparison_operator' => $alert->comparison_operator,
            'threshold_value' => $alert->threshold_value,
            'site_id' => $alert->site_id,
            'is_active' => $alert->is_active,
            'cooldown_minutes' => $alert->cooldown_minutes,
            'last_triggered_at' => optional($alert->last_triggered_at)->toIso8601String(),
            'last_value' => $alert->last_value,
            'definition' => $alert->definition(),
        ];
    }

    protected function serialiseEvent(KpiAlertEvent $event): array
    {
        return [
            'id' => $event->id,
            'kpi_alert_id' => $event->kpi_alert_id,
            'kpi_slug' => $event->alert?->kpi_slug,
            'kpi_name' => $event->alert?->definition()['name'] ?? $event->alert?->kpi_slug,
            'comparison_operator' => $event->alert?->comparison_operator,
            'threshold_value' => $event->alert?->threshold_value,
            'kpi_value' => $event->kpi_value,
            'triggered_at' => optional($event->triggered_at)->toIso8601String(),
            'read_at' => optional($event->read_at)->toIso8601String(),
            'context' => $event->context,
        ];
    }

    protected function authoriseAlert(Request $request, KpiAlert $alert): void
    {
        if ($alert->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    protected function alertsQuery(Request $request)
    {
        $query = $request->user()->kpiAlerts()->getQuery();
        if ($request->user()->isSuperAdmin()) {
            $siteFilter = $request->input('site_id');
            if ($siteFilter !== null && $siteFilter !== '') {
                $query->where('site_id', $siteFilter);
            }
        }
        return $query;
    }

    protected function eventsQuery(Request $request)
    {
        return $request->user()->kpiAlertEvents()->with('alert');
    }
}
