<?php

namespace App\Models;

use Database\Factories\MapRouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapRoute extends Model
{
    /** @use HasFactory<MapRouteFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'route_data',
    ];

    protected function casts(): array
    {
        return [
            'route_data' => 'array',
        ];
    }
}
