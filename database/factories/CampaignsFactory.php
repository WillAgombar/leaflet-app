<?php

namespace Database\Factories;

use App\Models\Campaigns;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaigns>
 */
class CampaignsFactory extends Factory
{
    protected $model = Campaigns::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+1 week');

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'start_date' => $startDate,
            'end_date' => fake()->optional()->dateTimeBetween($startDate, '+2 months'),
        ];
    }
}
