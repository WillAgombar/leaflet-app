<?php

namespace App\Models;

use Database\Factories\RouteAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteAssignment extends Model
{
    /** @use HasFactory<RouteAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_route_id',
        'user_id',
        'status',
        'assigned_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function campaignRoute(): BelongsTo
    {
        return $this->belongsTo(CampaignRoute::class, 'campaign_route_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
