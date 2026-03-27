<?php

namespace Database\Factories;

use App\Models\CampaignRoute;
use App\Models\Campaigns;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignRoute>
 */
class CampaignRouteFactory extends Factory
{
    protected $model = CampaignRoute::class;

    public function definition(): array
    {
        $startLatitude = fake()->latitude(37.70, 37.82);
        $startLongitude = fake()->longitude(-122.52, -122.35);

        return [
            'campaign_id' => Campaigns::factory(),
            'name' => fake()->words(2, true),
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
