<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\CalculatorController;
use App\Http\Controllers\Api\GazetteIngestionController;
use App\Http\Controllers\Api\HsCodeController;
use App\Http\Controllers\Api\TariffRevisionController;
use App\Http\Controllers\Api\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 1. Landed Cost Simulator (Public, Throttled to 60 req/min)
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/simulate/landed-cost', [CalculatorController::class, 'simulateLandedCost']);
        Route::post('/calculator/calculate', [CalculatorController::class, 'calculate']);
        Route::post('/calculator/compare', [CalculatorController::class, 'compare']);
        Route::get('/calculator/simulate/{code}', [CalculatorController::class, 'simulateByHsCode']);
    });

    // 2. HS Codes Master & Autocomplete Search
    Route::get('/hs-codes/search', [HsCodeController::class, 'search']);

    // 3. Gazette Notifications & Circulars (Filterable by authority & date)
    Route::get('/gazettes', [GazetteIngestionController::class, 'index']);
    Route::get('/circulars', [GazetteIngestionController::class, 'index']);
    Route::post('/circulars/poll', [GazetteIngestionController::class, 'poll']);
    Route::post('/circulars/simulate', [GazetteIngestionController::class, 'simulateGazette']);
    Route::post('/circulars/{circular}/reparse', [GazetteIngestionController::class, 'reparse']);

    // 4. Tariff Revisions (Filterable High Density Feed)
    Route::get('/revisions', [TariffRevisionController::class, 'index']);
    Route::get('/revisions/{revision}', [TariffRevisionController::class, 'show']);

    // 5. Portfolio Watchlist (GET / POST / DELETE)
    Route::get('/watchlist', [WatchlistController::class, 'index']);
    Route::post('/watchlist', [WatchlistController::class, 'store']);
    Route::delete('/watchlist', [WatchlistController::class, 'remove']);
    Route::put('/watchlist/{watchlist}', [WatchlistController::class, 'update']);
    Route::delete('/watchlist/{watchlist}', [WatchlistController::class, 'destroy']);

    // 6. Duty Shift Alerts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{alert}/read', [AlertController::class, 'markAsRead']);
    Route::post('/alerts/read-all', [AlertController::class, 'markAllAsRead']);
    Route::post('/alerts/{alert}/dismiss', [AlertController::class, 'dismiss']);
});
