<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PluginManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PluginController extends Controller
{
    public function index(PluginManager $plugins): Response
    {
        return Inertia::render('Admin/Plugins/Index', [
            'plugins' => $plugins->all(),
        ]);
    }

    public function enable(string $slug, PluginManager $plugins): RedirectResponse
    {
        $plugins->enable($slug);

        return back()->with('flash.success', "Plugin {$slug} enabled. Reload to take effect.");
    }

    public function disable(string $slug, PluginManager $plugins): RedirectResponse
    {
        $plugins->disable($slug);

        return back()->with('flash.success', "Plugin {$slug} disabled.");
    }
}
