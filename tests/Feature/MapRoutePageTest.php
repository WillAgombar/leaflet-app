<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapRoutePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_tracker_page_renders(): void
    {
        $response = $this->get(route('map-routes.show'));

        $response->assertOk();
        $response->assertSee('Leaflet Tracker');
    }

    public function test_route_can_be_saved(): void
    {
        $response = $this->postJson(route('map-routes.store'), [
            'name' => 'Michael Scott',
            'route_data' => [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'properties' => [],
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => [
                                [-122.422, 37.779],
                                [-122.418, 37.781],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('route.name', 'Michael Scott');
        $response->assertJsonPath('route.route.type', 'FeatureCollection');

        $this->assertDatabaseHas('map_routes', [
            'name' => 'Michael Scott',
        ]);
    }

    public function test_route_data_validation_is_enforced(): void
    {
        $response = $this->postJson(route('map-routes.store'), [
            'name' => '',
            'route_data' => [
                'type' => 'FeatureCollection',
                'features' => [],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'route_data.features']);
    }
}
