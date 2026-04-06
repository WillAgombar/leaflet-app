<?php

namespace Database\Factories;

use App\Models\RouteAssignment;
use App\Models\RouteTrackingPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouteTrackingPoint>
 */
class RouteTrackingPointFactory extends Factory
{
    protected $model = RouteTrackingPoint::class;

    public function definition(): array
    {
        $latitude = fake()->latitude();
        $longitude = fake()->longitude();

        return [
            'route_assignment_id' => RouteAssignment::factory(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'snapped_latitude' => $latitude,
            'snapped_longitude' => $longitude,
            'accuracy' => fake()->numberBetween(3, 15),
            'captured_at' => fake()->dateTimeBetween('-2 days', 'now'),
        ];
    }
}
