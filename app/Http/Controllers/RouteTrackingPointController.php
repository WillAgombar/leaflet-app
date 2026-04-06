<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouteTrackingPointRequest;
use App\Models\RouteAssignment;
use Illuminate\Http\JsonResponse;

class RouteTrackingPointController extends Controller
{
    public function index(RouteAssignment $assignment): JsonResponse
    {
        $this->ensureAssignmentAccess($assignment);

        $points = $assignment->trackingPoints()
            ->orderBy('captured_at')
            ->get([
                'id',
                'latitude',
                'longitude',
                'snapped_latitude',
                'snapped_longitude',
                'accuracy',
                'captured_at',
            ]);

        return response()->json([
            'data' => $points,
        ]);
    }

    public function store(StoreRouteTrackingPointRequest $request, RouteAssignment $assignment): JsonResponse
    {
        $this->ensureAssignmentAccess($assignment);

        $payload = $request->validated();

        $point = $assignment->trackingPoints()->create([
            'latitude' => $payload['latitude'],
            'longitude' => $payload['longitude'],
            'snapped_latitude' => $payload['snapped_latitude'] ?? null,
            'snapped_longitude' => $payload['snapped_longitude'] ?? null,
            'accuracy' => $payload['accuracy'] ?? null,
            'captured_at' => $payload['captured_at'] ?? now(),
        ]);

        return response()->json([
            'data' => $point,
        ], 201);
    }

    protected function ensureAssignmentAccess(RouteAssignment $assignment): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        if ($user->is_admin) {
            return;
        }

        if ($assignment->user_id !== $user->id) {
            abort(403);
        }
    }
}
