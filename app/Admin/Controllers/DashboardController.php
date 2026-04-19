<?php
namespace App\Admin\Controllers;

use App\Forum\Models\Forum;
use App\Forum\Models\Post;
use App\Forum\Models\Thread;
use App\Models\User;
use App\Moderation\Models\Report;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke(): \Inertia\Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users'   => User::count(),
                'threads' => Thread::withoutGlobalScope('active')->where('is_deleted', false)->count(),
                'posts'   => Post::withoutGlobalScope('active')->where('is_deleted', false)->count(),
                'forums'  => Forum::count(),
                'reports' => Report::where('status', 'open')->count(),
            ],
        ]);
    }
}
