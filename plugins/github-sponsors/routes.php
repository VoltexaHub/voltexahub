<?php

use App\Http\Controllers\Api\GitHubSponsorsController;
use App\Http\Controllers\Api\Admin\AdminGitHubSponsorsController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/github-sponsors', [GitHubSponsorsController::class, 'handle']);

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/github-sponsors', [AdminGitHubSponsorsController::class, 'index']);
    Route::post('/admin/github-sponsors/{id}/grant', [AdminGitHubSponsorsController::class, 'grant']);
    Route::post('/admin/github-sponsors/{id}/revoke', [AdminGitHubSponsorsController::class, 'revoke']);
});
