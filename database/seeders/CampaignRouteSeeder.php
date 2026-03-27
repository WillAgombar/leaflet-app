<?php

namespace Database\Seeders;

use App\Models\CampaignRoute;
use Illuminate\Database\Seeder;

class CampaignRouteSeeder extends Seeder
{
    public function run(): void
    {
        CampaignRoute::factory()->count(5)->create();
    }
}
