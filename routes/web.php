<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\MapRouteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/map', [MapRouteController::class, 'show'])->name('map-routes.show');
Route::post('/map-routes', [MapRouteController::class, 'store'])->name('map-routes.store');
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
