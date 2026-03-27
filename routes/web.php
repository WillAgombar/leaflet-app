<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignRouteController;
use App\Http\Controllers\MapRouteController;
use App\Http\Controllers\RouteAssignmentController;
use App\Models\RouteAssignment;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/campaigns');

Route::get('/map', [MapRouteController::class, 'show'])->name('map-routes.show');
Route::post('/map-routes', [MapRouteController::class, 'store'])->name('map-routes.store');
Route::get('/campaigns/{campaign}/map', [MapRouteController::class, 'showForCampaign'])->name('campaigns.map.show');
Route::post('/campaigns/{campaign}/map-routes', [MapRouteController::class, 'storeForCampaign'])->name('campaigns.map-routes.store');

Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');

Route::middleware('admin.user')->group(function (): void {
    Route::get('/campaigns/{campaign}/map/templates', [MapRouteController::class, 'showTemplates'])->name('campaigns.map.templates');
    Route::post('/campaigns/{campaign}/routes', [CampaignRouteController::class, 'store'])->name('campaigns.routes.store');
    Route::get('/campaigns/{campaign}/assignments', [RouteAssignmentController::class, 'index'])->name('campaigns.assignments.index');
    Route::post('/campaigns/{campaign}/routes/{campaignRoute}/assignments', [RouteAssignmentController::class, 'store'])->name('campaigns.assignments.store');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
});

Route::get('/assignments/{assignment}', [RouteAssignmentController::class, 'show'])->name('assignments.show');
Route::post('/assignments/{assignment}/start', [RouteAssignmentController::class, 'start'])->name('assignments.start');
Route::post('/assignments/{assignment}/complete', [RouteAssignmentController::class, 'complete'])->name('assignments.complete');

Route::get('/my-routes', function () {
    $assignedRoutes = collect();
    $completedRoutes = collect();

    if (auth()->check()) {
        $assignedRoutes = RouteAssignment::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['assigned', 'in_progress'])
            ->with('campaignRoute.campaign')
            ->latest('assigned_at')
            ->get();

        $completedRoutes = RouteAssignment::query()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->with('campaignRoute.campaign')
            ->latest('completed_at')
            ->get();
    }

    return view('routes.index', [
        'assignedRoutes' => $assignedRoutes,
        'completedRoutes' => $completedRoutes,
    ]);
})->name('routes.index');

Route::get('/profile', function () {
    $assignedCount = 0;
    $completedCount = 0;

    if (auth()->check()) {
        $assignedCount = RouteAssignment::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['assigned', 'in_progress'])
            ->count();

        $completedCount = RouteAssignment::query()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->count();
    }

    return view('profile.show', [
        'assignedCount' => $assignedCount,
        'completedCount' => $completedCount,
    ]);
})->name('profile.show');
