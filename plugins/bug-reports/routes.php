<?php

use Plugins\BugReports\Controllers\BugReportController;
use Plugins\BugReports\Controllers\StaffBugReportController;
use Illuminate\Support\Facades\Route;

// User-facing
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/bug-reports', [BugReportController::class, 'index']);
    Route::post('/bug-reports', [BugReportController::class, 'store']);
    Route::get('/bug-reports/{id}', [BugReportController::class, 'show']);
    Route::delete('/bug-reports/{id}', [BugReportController::class, 'destroy']);
});

// Staff-facing
Route::middleware(['auth:sanctum', 'staff'])->group(function () {
    Route::get('/staff/bug-reports', [StaffBugReportController::class, 'index']);
    Route::get('/staff/bug-reports/{id}', [StaffBugReportController::class, 'show']);
    Route::put('/staff/bug-reports/{id}', [StaffBugReportController::class, 'update']);
    Route::delete('/staff/bug-reports/{id}', [StaffBugReportController::class, 'destroy']);
});

// Admin panel
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/bug-reports', [StaffBugReportController::class, 'index']);
    Route::get('/admin/bug-reports/{id}', [StaffBugReportController::class, 'show']);
    Route::put('/admin/bug-reports/{id}', [StaffBugReportController::class, 'update']);
    Route::delete('/admin/bug-reports/{id}', [StaffBugReportController::class, 'destroy']);
});
