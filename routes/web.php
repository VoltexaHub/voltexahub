<?php
use App\Forum\Controllers\ForumIndexController;
use App\Forum\Controllers\ForumController;
use App\Forum\Controllers\ThreadController;
use App\Forum\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', ForumIndexController::class)->name('forum.index');
Route::get('/forum/{forum}', [ForumController::class, 'show'])->name('forum.show');
Route::get('/thread/{thread:slug}', [ThreadController::class, 'show'])->name('thread.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/forum/{forum}/new-thread', [ThreadController::class, 'create'])->name('thread.create');
    Route::post('/forum/{forum}/new-thread', [ThreadController::class, 'store'])->name('thread.store');
    Route::post('/thread/{thread:slug}/reply', [PostController::class, 'store'])->name('post.store');
    Route::put('/post/{post}', [PostController::class, 'update'])->name('post.update');
    Route::delete('/post/{post}', [PostController::class, 'destroy'])->name('post.destroy');
});

require __DIR__.'/auth.php';
