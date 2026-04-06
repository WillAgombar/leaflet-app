<?php

namespace App\Models;

use Database\Factories\RouteTrackingPointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteTrackingPoint extends Model
{
    /** @use HasFactory<RouteTrackingPointFactory> */
    use HasFactory;

    protected $fillable = [
        'route_assignment_id',
        'latitude',
        'longitude',
        'snapped_latitude',
        'snapped_longitude',
        'accuracy',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'snapped_latitude' => 'float',
            'snapped_longitude' => 'float',
            'accuracy' => 'float',
            'captured_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RouteAssignment::class, 'route_assignment_id');
    }
}
