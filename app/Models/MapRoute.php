<?php

namespace App\Models;

use Database\Factories\MapRouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapRoute extends Model
{
    /** @use HasFactory<MapRouteFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'name',
        'route_data',
    ];

    protected function casts(): array
    {
        return [
            'route_data' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaigns::class, 'campaign_id');
    }
}
