<?php

namespace App\Http\Controllers;

use App\Models\Campaigns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

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

    public function create(): View
    {
        return view('campaigns.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        Campaigns::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully!');
    }

    public function destroy(Campaigns $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted successfully.');
    }
}
