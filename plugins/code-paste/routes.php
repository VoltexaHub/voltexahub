<?php

use Illuminate\Support\Facades\Route;

// Public endpoint: returns Code Paste plugin configuration so the frontend
// knows whether the plugin is active and which settings to use.
Route::get('/plugins/code-paste/config', function () {
    return response()->json([
        'data' => [
            'enabled' => true,
            'theme' => 'github-dark',
            'show_line_numbers' => true,
            'show_copy_button' => true,
            'show_language_label' => true,
        ],
    ]);
});
