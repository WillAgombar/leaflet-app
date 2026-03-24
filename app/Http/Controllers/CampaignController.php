<?php

namespace App\Http\Controllers;

use App\Models\Campaigns;
use Illuminate\View\View;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = Campaigns::query()
            ->latest()
            ->get();

        return view('campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        return view('campaigns.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date'
        ]);

        Campaigns::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);

        return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully!');
    }
}
