<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRouteRequest;
use App\Models\CampaignRoute;
use App\Models\Campaigns;
use Illuminate\Http\JsonResponse;

class CampaignRouteController extends Controller
{
    public function store(StoreCampaignRouteRequest $request, Campaigns $campaign): JsonResponse
    {
        $payload = $request->validated();
        $payload['campaign_id'] = $campaign->id;

        $campaignRoute = CampaignRoute::query()->create($payload);

        return response()->json([
            'message' => 'Template saved successfully.',
            'route' => [
                'id' => $campaignRoute->id,
                'name' => $campaignRoute->name,
                'route' => $campaignRoute->route_data,
            ],
        ], 201);
    }
}
