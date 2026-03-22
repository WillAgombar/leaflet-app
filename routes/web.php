<?php

use App\Http\Controllers\MapRouteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', [MapRouteController::class, 'show'])->name('map-routes.show');
Route::post('/map-routes', [MapRouteController::class, 'store'])->name('map-routes.store');
