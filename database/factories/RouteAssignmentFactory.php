<?php

namespace Database\Factories;

use App\Models\CampaignRoute;
use App\Models\RouteAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouteAssignment>
 */
class RouteAssignmentFactory extends Factory
{
    protected $model = RouteAssignment::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['assigned', 'in_progress', 'completed']);
        $assignedAt = fake()->dateTimeBetween('-2 weeks', 'now');
        $completedAt = $status === 'completed'
            ? fake()->dateTimeBetween($assignedAt, 'now')
            : null;

        return [
            'campaign_route_id' => CampaignRoute::factory(),
            'user_id' => User::factory(),
            'status' => $status,
            'assigned_at' => $assignedAt,
            'completed_at' => $completedAt,
        ];
    }
}
