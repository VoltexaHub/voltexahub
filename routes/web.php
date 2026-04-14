<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumIndexController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PostEditController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', ForumIndexController::class)->name('home');
Route::get('/search', SearchController::class)->name('search');
Route::get('/users/{user}', UserProfileController::class)->name('users.show');
Route::get('/forums/{forum:slug}', [ForumController::class, 'show'])->name('forums.show');

Route::middleware('auth')->group(function () {
    Route::get('/forums/{forum:slug}/threads/create', [ThreadController::class, 'create'])->name('threads.create');
    Route::post('/forums/{forum:slug}/threads', [ThreadController::class, 'store'])->name('threads.store');
    Route::post('/forums/{forum:slug}/threads/{thread:slug}/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostEditController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostEditController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostEditController::class, 'destroy'])->name('posts.destroy');
    Route::post('/posts/{post}/report', [ReportController::class, 'store'])->name('posts.report');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/new', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/reply', [MessageController::class, 'reply'])->name('messages.reply');

    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->middleware('verified')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/forums/{forum:slug}/threads/{thread:slug}', [ThreadController::class, 'show'])->name('threads.show');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::resource('categories', Admin\CategoryController::class)->except(['show']);
    Route::resource('forums', Admin\ForumController::class)->except(['show']);
    Route::resource('threads', Admin\ThreadController::class)->only(['index', 'update', 'destroy']);
    Route::resource('users', Admin\UserController::class)->only(['index', 'update', 'destroy']);

    Route::get('plugins', [Admin\PluginController::class, 'index'])->name('plugins.index');
    Route::post('plugins/{slug}/enable', [Admin\PluginController::class, 'enable'])->name('plugins.enable');
    Route::post('plugins/{slug}/disable', [Admin\PluginController::class, 'disable'])->name('plugins.disable');

    Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::post('reports/{report}/dismiss', [Admin\ReportController::class, 'dismiss'])->name('reports.dismiss');
    Route::post('reports/{report}/resolve-delete', [Admin\ReportController::class, 'resolveDelete'])->name('reports.resolve-delete');

    Route::get('settings', [Admin\SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/oauth-secret', [Admin\SettingController::class, 'clearSecret'])->name('settings.oauth.clear-secret');
});

require __DIR__.'/auth.php';
