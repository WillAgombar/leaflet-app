<?php

namespace Database\Seeders;

use App\Models\RouteTrackingPoint;
use Illuminate\Database\Seeder;

class RouteTrackingPointSeeder extends Seeder
{
    public function run(): void
    {
        RouteTrackingPoint::factory()->count(10)->create();
    }
}
