<?php

declare(strict_types=1);

use App\Http\Controllers\TerminalDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TerminalDashboardController::class, 'index'])->name('terminal.index');
Route::post('/switch-user/{userId}', [TerminalDashboardController::class, 'switchUser'])->name('terminal.switch-user');
