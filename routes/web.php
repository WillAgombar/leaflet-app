<?php

use App\Http\Controllers\MapRouteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/map', [MapRouteController::class, 'show'])->name('map-routes.show');
Route::post('/map-routes', [MapRouteController::class, 'store'])->name('map-routes.store');
Route::get('/campaigns', function () {
    return view('campaigns');
})->name('campaigns.index');
