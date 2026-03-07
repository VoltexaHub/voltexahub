<?php

use App\Http\Controllers\Api\Admin\AdminStatusController;
use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

// Public API endpoint — no auth required
Route::get('/status', [StatusController::class, 'index']);

// Admin endpoints
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/status', [AdminStatusController::class, 'index']);
    Route::post('/admin/status/override', [AdminStatusController::class, 'override']);
    Route::delete('/admin/status/override/{service}', [AdminStatusController::class, 'clearOverride']);
    Route::delete('/admin/status/overrides', [AdminStatusController::class, 'clearAllOverrides']);
});
