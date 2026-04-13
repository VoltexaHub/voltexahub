<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumIndexController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostEditController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ThreadController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', ForumIndexController::class)->name('home');
Route::get('/forums/{forum:slug}', [ForumController::class, 'show'])->name('forums.show');

Route::middleware('auth')->group(function () {
    Route::get('/forums/{forum:slug}/threads/create', [ThreadController::class, 'create'])->name('threads.create');
    Route::post('/forums/{forum:slug}/threads', [ThreadController::class, 'store'])->name('threads.store');
    Route::post('/forums/{forum:slug}/threads/{thread:slug}/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostEditController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostEditController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostEditController::class, 'destroy'])->name('posts.destroy');

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
});

require __DIR__.'/auth.php';
