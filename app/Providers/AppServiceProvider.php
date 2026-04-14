<?php

namespace App\Providers;

use App\Models\Conversation;
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
        });
    }
}
