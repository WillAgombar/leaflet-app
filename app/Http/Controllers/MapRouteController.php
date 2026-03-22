<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMapRouteRequest;
use App\Models\MapRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MapRouteController extends Controller
{
    public function show(): View
    {
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

        return view('test', [
            'mapRoutes' => $mapRoutes,
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
}
