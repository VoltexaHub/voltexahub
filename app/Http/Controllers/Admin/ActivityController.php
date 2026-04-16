<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = AdminActivity::query()
            ->with('user:id,name')
            ->when($request->string('action')->toString(), fn ($q, $a) => $q->where('action', $a))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $actions = AdminActivity::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return Inertia::render('Admin/Activity/Index', [
            'entries' => $entries,
            'actions' => $actions,
            'filters' => ['action' => $request->string('action')->toString()],
        ]);
    }
}
