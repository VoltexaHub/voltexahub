<?php

/**
 * Welcome Banner plugin bootstrap.
 *
 * Available in scope:
 * @var \Illuminate\Contracts\Foundation\Application $app
 * @var \App\Services\HookManager $hooks
 * @var array $plugin  (manifest merged with runtime metadata)
 */

use Illuminate\Support\Facades\Route;

$hooks->listen('before_content', function () use ($plugin) {
    if (! request()->routeIs('home')) {
        return null;
    }

    return view('plugin.'.$plugin['slug'].'::banner')->render();
});
