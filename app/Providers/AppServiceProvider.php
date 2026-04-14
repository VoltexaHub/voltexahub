<?php

namespace App\Providers;

use App\Models\Conversation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiters();

        view()->composer('theme::*', function ($view) {
            $user = auth()->user();
            $unread = 0;
            if ($user) {
                $unread = Conversation::query()
                    ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
                    ->whereHas('messages', fn ($q) => $q->where('user_id', '!=', $user->id))
                    ->whereExists(function ($q) use ($user) {
                        $q->selectRaw('1')
                            ->from('conversation_user')
                            ->whereColumn('conversation_user.conversation_id', 'conversations.id')
                            ->where('conversation_user.user_id', $user->id)
                            ->where(function ($w) {
                                $w->whereNull('conversation_user.last_read_at')
                                  ->orWhereColumn('conversations.last_message_at', '>', 'conversation_user.last_read_at');
                            });
                    })
                    ->count();
            }
            $view->with('unreadMessages', $unread);
            $view->with('unreadNotifications', $user ? $user->unreadNotifications()->count() : 0);
        });
    }

    private function configureRateLimiters(): void
    {
        $byUser = fn (Request $request) => $request->user()?->id ?: $request->ip();
        $adminBypass = fn (Request $request) => $request->user()?->is_admin
            ? Limit::none()
            : null;

        RateLimiter::for('threads.create', function (Request $request) use ($byUser, $adminBypass) {
            return $adminBypass($request) ?? Limit::perHour(5)->by($byUser($request));
        });
        RateLimiter::for('posts.create', function (Request $request) use ($byUser, $adminBypass) {
            return $adminBypass($request) ?? Limit::perHour(30)->by($byUser($request));
        });
        RateLimiter::for('posts.report', function (Request $request) use ($byUser) {
            return Limit::perHour(10)->by($byUser($request));
        });
        RateLimiter::for('messages.send', function (Request $request) use ($byUser, $adminBypass) {
            return $adminBypass($request) ?? Limit::perHour(30)->by($byUser($request));
        });
        RateLimiter::for('avatar.update', function (Request $request) use ($byUser) {
            return Limit::perHour(20)->by($byUser($request));
        });
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
