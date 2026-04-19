<?php
use App\Admin\Controllers\CategoryController;
use App\Admin\Controllers\DashboardController;
use App\Admin\Controllers\ForumController;
use App\Admin\Controllers\GroupController;
use App\Admin\Controllers\PostController;
use App\Admin\Controllers\SettingController;
use App\Admin\Controllers\ThreadController;
use App\Admin\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
    Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
    Route::post('forums/reorder', [ForumController::class, 'reorder'])->name('forums.reorder');
    Route::resource('forums', ForumController::class)->except(['show', 'create', 'edit']);
    Route::resource('threads', ThreadController::class)->only(['index', 'destroy']);
    Route::resource('posts', PostController::class)->only(['index', 'update', 'destroy']);
    Route::resource('users', UserController::class)->except(['create', 'store', 'show', 'edit']);
    Route::post('users/{user}/ban', [UserController::class, 'ban'])->name('users.ban');
    Route::post('users/{user}/unban', [UserController::class, 'unban'])->name('users.unban');
    Route::resource('groups', GroupController::class)->except(['show']);
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
});
