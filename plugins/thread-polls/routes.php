<?php

use App\Http\Controllers\Api\PollController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/threads/{thread}/poll', [PollController::class, 'store']);
    Route::post('/polls/{poll}/vote', [PollController::class, 'vote']);
    Route::delete('/polls/{poll}/vote', [PollController::class, 'removeVote']);
});

Route::get('/polls/{poll}', [PollController::class, 'show']);
