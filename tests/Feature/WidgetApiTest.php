<?php

namespace Tests\Feature;

use App\Models\DashboardWidget;
use App\Models\User;
use App\Support\WidgetDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_returns_widget_definitions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/widgets/catalog');

        $response
            ->assertOk()
            ->assertJsonCount(count(WidgetDefaults::catalog()), 'widgets')
            ->assertJsonFragment(['slug' => 'devices_per_site'])
            ->assertJsonFragment(['slug' => 'histogram_chart']);
    }

    public function test_dashboard_returns_widgets_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/widgets/attach', ['slug' => 'histogram_chart'])
            ->assertCreated();

        $response = $this->actingAs($user)->getJson('/api/widgets/dashboard');

        $response
            ->assertOk()
            ->assertJsonPath('title', 'Panel principal')
            ->assertJsonCount(1, 'widgets')
            ->assertJsonPath('widgets.0.slug', 'histogram_chart');
    }

    public function test_update_widget_allows_reordering_and_filters(): void
    {
        $user = User::factory()->create();

        $first = $this->actingAs($user)
            ->postJson('/api/widgets/attach', ['slug' => 'histogram_chart'])
            ->json('widget.id');

        $secondResponse = $this->actingAs($user)
            ->postJson('/api/widgets/attach', ['slug' => 'devices_per_site']);

        $second = $secondResponse->json('widget.id');

        $this->actingAs($user)
            ->patchJson("/api/widgets/{$second}", [
                'position_index' => 0,
                'data_filters' => ['siteId' => '123'],
            ])
            ->assertOk()
            ->assertJsonPath('widget.position_index', 0)
            ->assertJsonPath('widget.data_filters.siteId', '123');

        $widgets = DashboardWidget::orderBy('position_index')->get()->pluck('id')->all();
        $this->assertSame([$second, $first], $widgets);
    }
}
