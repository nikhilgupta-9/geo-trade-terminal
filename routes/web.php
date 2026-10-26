<?php

declare(strict_types=1);

use App\Http\Controllers\TerminalDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TerminalDashboardController::class, 'index'])->name('terminal.index');
Route::post('/switch-user/{userId}', [TerminalDashboardController::class, 'switchUser'])->name('terminal.switch-user');

// Optional decoupled React build mount point (serves public/react/index.html if present)
Route::get('/app/{any?}', function () {
    $reactIndex = public_path('react/index.html');
    if (file_exists($reactIndex)) {
        return response()->file($reactIndex);
    }
    return redirect()->route('terminal.index');
})->where('any', '.*');
