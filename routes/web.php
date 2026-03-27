<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\MapRouteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/campaigns');

Route::get('/map', [MapRouteController::class, 'show'])->name('map-routes.show');
Route::post('/map-routes', [MapRouteController::class, 'store'])->name('map-routes.store');
Route::get('/campaigns/{campaign}/map', [MapRouteController::class, 'showForCampaign'])->name('campaigns.map.show');
Route::post('/campaigns/{campaign}/map-routes', [MapRouteController::class, 'storeForCampaign'])->name('campaigns.map-routes.store');

Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');

Route::middleware('admin.token')->group(function (): void {
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
});
