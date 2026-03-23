<?php

namespace App\Http\Controllers;

use App\Models\Campaigns;
use Illuminate\View\View;

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
}
