<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired --hours=720')->daily();
Schedule::command('backup:database')->dailyAt('03:00');

Schedule::call(function () {
    if (\App\Models\ForumConfig::get('error_log_enabled') === 'true') {
        $days = (int) (\App\Models\ForumConfig::get('error_log_prune_days', '30'));
        \App\Models\ErrorLog::where('created_at', '<', now()->subDays($days))->delete();
    }
})->daily();
