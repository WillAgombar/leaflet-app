<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouteAssignmentRequest;
use App\Models\CampaignRoute;
use App\Models\Campaigns;
use App\Models\RouteAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteAssignmentController extends Controller
{
    public function index(Campaigns $campaign): View
    {
        $campaignRoutes = CampaignRoute::query()
            ->where('campaign_id', $campaign->id)
            ->with(['assignment.user'])
            ->latest()
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $totalRoutes = $campaignRoutes->count();
        $assignedRoutes = $campaignRoutes->filter(function (CampaignRoute $campaignRoute): bool {
            return $campaignRoute->assignment?->status === 'assigned';
        })->count();
        $inProgressRoutes = $campaignRoutes->filter(function (CampaignRoute $campaignRoute): bool {
            return $campaignRoute->assignment?->status === 'in_progress';
        })->count();
        $completedRoutes = $campaignRoutes->filter(function (CampaignRoute $campaignRoute): bool {
            return $campaignRoute->assignment?->status === 'completed';
        })->count();

        return view('campaigns.assignments.index', [
            'campaign' => $campaign,
            'campaignRoutes' => $campaignRoutes,
            'users' => $users,
            'totalRoutes' => $totalRoutes,
            'assignedRoutes' => $assignedRoutes,
            'inProgressRoutes' => $inProgressRoutes,
            'completedRoutes' => $completedRoutes,
        ]);
    }

    public function store(StoreRouteAssignmentRequest $request, Campaigns $campaign, CampaignRoute $campaignRoute): RedirectResponse
    {
        if ($campaignRoute->campaign_id !== $campaign->id) {
            abort(404);
        }

        $payload = $request->validated();

        $assignment = RouteAssignment::query()
            ->where('campaign_route_id', $campaignRoute->id)
            ->first();

        if ($assignment) {
            $assignment->fill([
                'user_id' => $payload['user_id'],
                'status' => 'assigned',
                'assigned_at' => now(),
                'completed_at' => null,
            ])->save();
        } else {
            $assignment = RouteAssignment::query()->create([
                'campaign_route_id' => $campaignRoute->id,
                'user_id' => $payload['user_id'],
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);
        }

        return redirect()
            ->route('campaigns.assignments.index', $campaign)
            ->with('success', 'Assignment saved successfully.');
    }

    public function show(RouteAssignment $assignment): View
    {
        $assignment->load(['campaignRoute.campaign', 'user']);

        if (auth()->id() !== $assignment->user_id) {
            abort(403);
        }

        return view('assignments.show', [
            'assignment' => $assignment,
            'campaignRoute' => $assignment->campaignRoute,
        ]);
    }

    public function start(Request $request, RouteAssignment $assignment): JsonResponse
    {
        if (auth()->id() !== $assignment->user_id) {
            abort(403);
        }

        if ($assignment->status !== 'assigned') {
            return response()->json([
                'message' => 'This route is already in progress or completed.',
            ], 422);
        }

        $assignment->update([
            'status' => 'in_progress',
        ]);

        return response()->json([
            'message' => 'Route marked as in progress.',
            'status' => $assignment->status,
        ]);
    }

    public function complete(Request $request, RouteAssignment $assignment): JsonResponse
    {
        if (auth()->id() !== $assignment->user_id) {
            abort(403);
        }

        if ($assignment->status !== 'in_progress') {
            return response()->json([
                'message' => 'This route must be started before it can be completed.',
            ], 422);
        }

        $assignment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Route marked as completed.',
            'status' => $assignment->status,
        ]);
    }
}
