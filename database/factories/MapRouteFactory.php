<?php

namespace Database\Factories;

use App\Models\MapRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapRoute>
 */
class MapRouteFactory extends Factory
{
    public function definition(): array
    {
        $startLatitude = fake()->latitude(37.70, 37.82);
        $startLongitude = fake()->longitude(-122.52, -122.35);

        return [
            'name' => fake()->name(),
            'route_data' => [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'properties' => [],
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => [
                                [$startLongitude, $startLatitude],
                                [$startLongitude + fake()->randomFloat(5, 0.003, 0.018), $startLatitude + fake()->randomFloat(5, 0.002, 0.017)],
                                [$startLongitude + fake()->randomFloat(5, 0.02, 0.035), $startLatitude + fake()->randomFloat(5, 0.01, 0.028)],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
