<?php

namespace Database\Seeders;

use App\Models\MapRoute;
use Illuminate\Database\Seeder;

class MapRouteSeeder extends Seeder
{
    public function run(): void
    {
        MapRoute::factory()->count(5)->create();
    }
}
