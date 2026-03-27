<?php

namespace Database\Seeders;

use App\Models\RouteAssignment;
use Illuminate\Database\Seeder;

class RouteAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        RouteAssignment::factory()->count(5)->create();
    }
}
