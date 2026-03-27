<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMapRouteRequest;
use App\Models\CampaignRoute;
use App\Models\Campaigns;
use App\Models\MapRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MapRouteController extends Controller
{
    public function show(): View
    {
        $isAdmin = auth()->check() && auth()->user()?->is_admin === true;

        $mapRoutes = MapRoute::query()
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (MapRoute $mapRoute): array {
                return [
                    'id' => $mapRoute->id,
                    'name' => $mapRoute->name,
                    'route' => $mapRoute->route_data,
                ];
            })
            ->values();

        return view('map', [
            'mapRoutes' => $mapRoutes,
            'saveUrl' => route('map-routes.store'),
            'campaign' => null,
            'mode' => 'volunteer',
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(StoreMapRouteRequest $request): JsonResponse
    {
        $mapRoute = MapRoute::query()->create($request->validated());

        return response()->json([
            'message' => 'Route saved successfully.',
            'route' => [
                'id' => $mapRoute->id,
                'name' => $mapRoute->name,
                'route' => $mapRoute->route_data,
            ],
        ], 201);
    }

    public function showForCampaign(Campaigns $campaign): View
    {
        $isAdmin = auth()->check() && auth()->user()?->is_admin === true;
        $mode = 'volunteer';
        $saveUrl = route('campaigns.map-routes.store', $campaign);

        $mapRoutes = MapRoute::query()
            ->where('campaign_id', $campaign->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (MapRoute $mapRoute): array {
                return [
                    'id' => $mapRoute->id,
                    'name' => $mapRoute->name,
                    'route' => $mapRoute->route_data,
                ];
            })
            ->values();

        return view('map', [
            'mapRoutes' => $mapRoutes,
            'saveUrl' => $saveUrl,
            'campaign' => $campaign,
            'mode' => $mode,
            'isAdmin' => $isAdmin,
            'modeToggleUrl' => $isAdmin ? route('campaigns.map.templates', $campaign) : null,
        ]);
    }

    public function showTemplates(Campaigns $campaign): View
    {
        if (!auth()->check() || auth()->user()?->is_admin !== true) {
            abort(403);
        }

        $mapRoutes = CampaignRoute::query()
            ->where('campaign_id', $campaign->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (CampaignRoute $campaignRoute): array {
                return [
                    'id' => $campaignRoute->id,
                    'name' => $campaignRoute->name,
                    'route' => $campaignRoute->route_data,
                ];
            })
            ->values();

        return view('map', [
            'mapRoutes' => $mapRoutes,
            'saveUrl' => route('campaigns.routes.store', $campaign),
            'campaign' => $campaign,
            'mode' => 'template',
            'isAdmin' => true,
            'modeToggleUrl' => route('campaigns.map.show', $campaign),
        ]);
    }

    public function storeForCampaign(StoreMapRouteRequest $request, Campaigns $campaign): JsonResponse
    {
        $payload = $request->validated();
        $payload['campaign_id'] = $campaign->id;

        $mapRoute = MapRoute::query()->create($payload);

        return response()->json([
            'message' => 'Route saved successfully.',
            'route' => [
                'id' => $mapRoute->id,
                'name' => $mapRoute->name,
                'route' => $mapRoute->route_data,
            ],
        ], 201);
    }
}
