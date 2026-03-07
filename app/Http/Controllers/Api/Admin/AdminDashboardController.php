<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Report;
use App\Models\StorePurchase;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $userCount = User::count();
        $postCount = Post::count();
        $threadCount = Thread::count();
        $onlineCount = User::where('last_seen', '>=', Carbon::now()->subMinutes(15))->count();
        $pendingReports = Report::where('status', 'pending')->count();

        $postsToday = Post::whereDate('created_at', today())->count();
        $threadsToday = Thread::whereDate('created_at', today())->count();
        $usersToday = User::whereDate('created_at', today())->count();
        $totalCredits = (int) User::sum('credits');

        $revenueThisMonth = StorePurchase::where('status', 'completed')
            ->where('payment_method', 'money')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_paid');

        $topForums = Forum::orderByDesc('thread_count')
            ->take(5)
            ->get(['id', 'name', 'slug', 'thread_count', 'post_count']);

        // Recent activity: mix of registrations, purchases, and threads
        $recentUsers = User::latest()
            ->take(10)
            ->get(['id', 'username', 'created_at'])
            ->map(fn ($u) => [
                'type' => 'registration',
                'user' => $u->username,
                'at' => $u->created_at,
            ]);

        $recentPurchasesActivity = StorePurchase::with(['user:id,username', 'storeItem:id,name'])
            ->where('status', 'completed')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($p) => [
                'type' => 'purchase',
                'user' => $p->user->username ?? 'Unknown',
                'item' => $p->storeItem->name ?? 'Unknown',
                'at' => $p->created_at,
            ]);

        $recentThreads = Thread::with('user:id,username')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($t) => [
                'type' => 'thread',
                'user' => $t->user->username ?? 'Unknown',
                'title' => $t->title,
                'at' => $t->created_at,
            ]);

        $recentActivity = $recentUsers
            ->concat($recentPurchasesActivity)
            ->concat($recentThreads)
            ->sortByDesc('at')
            ->take(10)
            ->values();

        $recentRegistrations = User::latest()
            ->take(5)
            ->get(['id', 'username', 'created_at', 'avatar_color', 'avatar_path']);

        $recentPurchases = StorePurchase::with(['user:id,username', 'storeItem:id,name'])
            ->where('status', 'completed')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'user' => $p->user->username ?? 'Unknown',
                'item_name' => $p->storeItem->name ?? 'Unknown',
                'amount_paid' => $p->amount_paid,
                'payment_method' => $p->payment_method,
                'created_at' => $p->created_at,
            ]);

        return response()->json([
            'data' => [
                'user_count' => $userCount,
                'post_count' => $postCount,
                'thread_count' => $threadCount,
                'online_count' => $onlineCount,
                'pending_reports' => $pendingReports,
                'revenue_this_month' => (float) $revenueThisMonth,
                'posts_today' => $postsToday,
                'threads_today' => $threadsToday,
                'users_today' => $usersToday,
                'total_credits' => $totalCredits,
                'top_forums' => $topForums,
                'recent_activity' => $recentActivity,
            ],
            'recent_registrations' => $recentRegistrations,
            'recent_purchases' => $recentPurchases,
        ]);
    }
}
