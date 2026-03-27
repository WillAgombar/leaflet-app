<?php

namespace App\Models;

use Database\Factories\CampaignRouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CampaignRoute extends Model
{
    /** @use HasFactory<CampaignRouteFactory> */
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

    public function assignment(): HasOne
    {
        return $this->hasOne(RouteAssignment::class, 'campaign_route_id');
    }
}
