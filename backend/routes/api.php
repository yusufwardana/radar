<?php

use App\Http\Controllers\Api\V1\SignalController;
use App\Http\Controllers\Api\V1\SourceController;
use App\Http\Controllers\Api\V1\BmkgSignalController;
use App\Http\Controllers\Api\V1\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::get('signals', [SignalController::class, 'index']);
    Route::get('signals/map', [BmkgSignalController::class, 'map']);
    Route::get('signals/{signal}', [SignalController::class, 'show']);
    Route::get('sources', [SourceController::class, 'index']);
    Route::get('sources/{source}', [SourceController::class, 'show']);
    Route::get('earthquakes', [BmkgSignalController::class, 'earthquakes']);
    Route::get('earthquakes/{signal}', [BmkgSignalController::class, 'earthquake']);
    Route::get('weather/alerts', [BmkgSignalController::class, 'alerts']);
    Route::get('weather/alerts/{signal}', [BmkgSignalController::class, 'alert']);
    Route::get('locations', [LocationController::class, 'index']);
    Route::get('locations/{location}', [LocationController::class, 'show']);
    Route::get('locations/{location}/forecast', [LocationController::class, 'forecast']);
});