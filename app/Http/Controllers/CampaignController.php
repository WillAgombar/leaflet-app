<?php

namespace App\Http\Controllers;

use App\Models\CampaignRoute;
use App\Models\Campaigns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $isAdmin = auth()->check() && auth()->user()?->is_admin === true;

        $campaigns = Campaigns::query()
            ->withCount('mapRoutes')
            ->withCount([
                'mapRoutes as volunteers_count' => function ($query) {
                    $query->select(DB::raw('count(distinct name)'));
                },
            ])
            ->latest()
            ->get();

        return view('campaigns.index', compact('campaigns', 'isAdmin'));
    }

    public function show(Campaigns $campaign): View
    {
        $isAdmin = auth()->check() && auth()->user()?->is_admin === true;
        $campaign->load(['campaignRoutes.assignment.user']);

        return view('campaigns.show', compact('campaign', 'isAdmin'));
    }

    public function create(Request $request): View
    {
        $duplicateFrom = null;

        if ($request->has('duplicate_from')) {
            $duplicateFrom = Campaigns::find($request->duplicate_from);
        }

        return view('campaigns.create', compact('duplicateFrom'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'duplicate_from' => 'nullable|exists:campaigns,id',
        ]);

        $campaign = Campaigns::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        if ($request->has('duplicate_from')) {
            $sourceCampaign = Campaigns::with('campaignRoutes')->find($request->duplicate_from);

            if ($sourceCampaign) {
                foreach ($sourceCampaign->campaignRoutes as $route) {
                    CampaignRoute::create([
                        'campaign_id' => $campaign->id,
                        'name' => $route->name,
                        'route_data' => $route->route_data,
                    ]);
                }
            }
        }

        return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully!');
    }

    public function destroy(Campaigns $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted successfully.');
    }
}
