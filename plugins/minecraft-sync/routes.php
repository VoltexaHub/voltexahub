<?php

use App\Http\Controllers\Api\MinecraftController;
use App\Http\Controllers\Api\Admin\AdminMinecraftController;
use Illuminate\Support\Facades\Route;

// Public API routes (called by Minecraft server plugin)
Route::post('/minecraft/verify', [MinecraftController::class, 'verify']);
Route::get('/minecraft/player/{uuid}', [MinecraftController::class, 'player']);
Route::post('/minecraft/webhook', [MinecraftController::class, 'webhook']);
Route::post('/minecraft/redeem', [MinecraftController::class, 'redeem']);

// Authenticated routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/minecraft/link', [MinecraftController::class, 'link']);
    Route::delete('/minecraft/link', [MinecraftController::class, 'unlink']);
    Route::get('/minecraft/status', [MinecraftController::class, 'status']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/minecraft/codes', [AdminMinecraftController::class, 'codes']);
    Route::post('/admin/minecraft/codes', [AdminMinecraftController::class, 'createCode']);
    Route::delete('/admin/minecraft/codes/{id}', [AdminMinecraftController::class, 'deleteCode']);
});
