<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Preflight extends Command
{
    protected $signature = 'app:preflight';
    protected $description = 'Sanity-check the current deployment. Exits non-zero on any blocker.';

    public function handle(): int
    {
        $blockers = 0;
        $warnings = 0;

        $this->info('VoltexaHub preflight — '.config('app.name'));
        $this->newLine();

        // Environment
        $env = config('app.env');
        $debug = config('app.debug');
        $key = config('app.key');

        $this->check('APP_KEY is set', (bool) $key, true, $blockers);
        $this->check('APP_ENV=production', $env === 'production', false, $warnings, "current: {$env}");
        $this->check('APP_DEBUG=false', $debug === false, true, $blockers, $debug ? 'DEBUG is enabled — will leak stack traces' : null);
        $this->check('APP_URL uses https', str_starts_with((string) config('app.url'), 'https://'), false, $warnings);

        // Database
        try {
            DB::connection()->getPdo();
            $this->check('Database connection', true, true, $blockers);
        } catch (\Throwable $e) {
            $this->check('Database connection', false, true, $blockers, $e->getMessage());
        }

        $pending = 0;
        try {
            $pending = count(app('migrator')->getRepository()->getRan()) !== count($this->collectMigrationFiles())
                ? 1 : 0;
        } catch (\Throwable) {
            $pending = 0;
        }
        $this->check('Migrations up to date', $pending === 0, true, $blockers, $pending ? 'Run `php artisan migrate --force`' : null);

        $this->check('At least one admin exists', User::where('is_admin', true)->exists(), true, $blockers);

        // Storage + uploads
        $this->check('storage/app/public is writable', is_writable(storage_path('app/public')), true, $blockers);
        $this->check('public/storage symlink exists', file_exists(public_path('storage')), true, $blockers, 'Run `php artisan storage:link`');

        // Cache, queue, mail
        $this->check('Cache store is not array',
            config('cache.default') !== 'array', false, $warnings,
            'array cache wipes on every request');
        $this->check('Queue driver is not sync',
            config('queue.default') !== 'sync', false, $warnings,
            'sync means mail + notifications block the request');
        $this->check('Mailer is configured',
            config('mail.default') !== 'log', false, $warnings,
            'log mailer is for dev only');

        // Broadcasting
        $this->check('Broadcast driver is reverb',
            config('broadcasting.default') === 'reverb', false, $warnings);

        // Reverb host reachability (best-effort)
        $reverb = config('broadcasting.connections.reverb.options.host');
        if ($reverb) {
            $this->check('Reverb host set', true, false, $warnings);
        }

        // OAuth
        $gh = config('services.github.client_id');
        $gg = config('services.google.client_id');
        $this->check('At least one OAuth provider configured', (bool) ($gh || $gg), false, $warnings,
            'Email+password still works, but OAuth buttons will stay hidden');

        // Error tracking
        $this->check('Error tracking configured (Sentry)', (bool) config('sentry.dsn'), false, $warnings,
            'Set SENTRY_LARAVEL_DSN to capture production errors');

        $this->newLine();
        $this->line(sprintf(
            '<fg=%s>%s</>  · %d blocker%s, %d warning%s',
            $blockers === 0 ? 'green' : 'red',
            $blockers === 0 ? ($warnings === 0 ? 'READY' : 'READY WITH WARNINGS') : 'NOT READY',
            $blockers, $blockers === 1 ? '' : 's',
            $warnings, $warnings === 1 ? '' : 's',
        ));

        return $blockers === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function check(string $label, bool $ok, bool $isBlocker, int &$counter, ?string $hint = null): void
    {
        $icon = $ok ? '<fg=green>✓</>' : ($isBlocker ? '<fg=red>✗</>' : '<fg=yellow>!</>');
        $this->line("  {$icon} {$label}".($hint ? " <fg=gray>— {$hint}</>" : ''));
        if (! $ok) $counter++;
    }

    private function collectMigrationFiles(): array
    {
        return glob(database_path('migrations/*.php')) ?: [];
    }
}
