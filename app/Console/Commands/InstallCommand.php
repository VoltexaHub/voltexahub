<?php

namespace App\Console\Commands;

use App\Models\ForumConfig;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $signature = 'voltexahub:install
        {--forum-name= : Forum name (skips interactive prompt)}
        {--forum-url= : Forum URL (skips interactive prompt)}
        {--admin-username= : Admin username (skips interactive prompt)}
        {--admin-email= : Admin email (skips interactive prompt)}
        {--admin-password= : Admin password (skips interactive prompt)}
        {--no-seed : Skip running seeders}';

    protected $description = 'Interactive installer for VoltexaHub (supports non-interactive mode via options)';

    public function handle(): int
    {
        $this->info('');
        $this->info('========================================');
        $this->info('       VoltexaHub Installer');
        $this->info('========================================');
        $this->info('');

        // Determine if running in non-interactive mode (all required options provided)
        $nonInteractive = $this->option('forum-name')
            && $this->option('forum-url')
            && $this->option('admin-username')
            && $this->option('admin-email')
            && $this->option('admin-password');

        // Step 1: Check if already installed
        if (Schema::hasTable('users') && User::count() > 0) {
            if ($nonInteractive) {
                $this->warn('VoltexaHub appears to already be installed. Continuing in non-interactive mode...');
            } elseif (! $this->confirm('VoltexaHub appears to already be installed. Do you want to re-run the installer?')) {
                $this->info('Installation cancelled.');
                return 0;
            }
        }

        // Collect config — use options if provided, otherwise prompt interactively
        $forumName = $this->option('forum-name') ?? $this->ask('What is your forum name?', 'My Forum');
        $forumUrl = $this->option('forum-url') ?? $this->ask('What is your forum URL?', 'http://localhost:8000');
        $adminUsername = $this->option('admin-username') ?? $this->ask('Admin username');
        $adminEmail = $this->option('admin-email') ?? $this->ask('Admin email');

        if ($this->option('admin-password')) {
            $adminPassword = $this->option('admin-password');
        } else {
            $adminPassword = $this->secret('Admin password');
            $adminPasswordConfirm = $this->secret('Confirm admin password');

            if ($adminPassword !== $adminPasswordConfirm) {
                $this->error('Passwords do not match. Please try again.');
                return 1;
            }
        }

        // Database connection — only prompt in interactive mode
        if ($nonInteractive) {
            // In non-interactive mode, use whatever DB_CONNECTION is already in .env
            $dbConnection = config('database.default', 'sqlite');
        } else {
            $dbConnection = $this->choice('Database connection', ['sqlite', 'mysql'], 0);
        }

        $dbHost = $dbPort = $dbDatabase = $dbUsername = $dbPassword = null;
        if (! $nonInteractive && $dbConnection === 'mysql') {
            $dbHost = $this->ask('MySQL host', '127.0.0.1');
            $dbPort = $this->ask('MySQL port', '3306');
            $dbDatabase = $this->ask('Database name', 'voltexahub');
            $dbUsername = $this->ask('Database username', 'root');
            $dbPassword = $this->secret('Database password');
        }

        $this->info('');
        $this->info('Setting up VoltexaHub...');
        $this->info('');

        // Generate APP_KEY if not set
        if (empty(env('APP_KEY'))) {
            $this->info('Generating application key...');
            Artisan::call('key:generate', ['--force' => true]);
            $this->info('  Application key generated.');
        }

        // Write .env values (skip DB config in non-interactive mode — install.sh handles it)
        $this->info('Writing configuration...');
        $this->updateEnv('APP_NAME', $forumName);
        $this->updateEnv('APP_URL', $forumUrl);

        if (! $nonInteractive) {
            $this->updateEnv('DB_CONNECTION', $dbConnection);
            if ($dbConnection === 'mysql') {
                $this->updateEnv('DB_HOST', $dbHost);
                $this->updateEnv('DB_PORT', $dbPort);
                $this->updateEnv('DB_DATABASE', $dbDatabase);
                $this->updateEnv('DB_USERNAME', $dbUsername);
                $this->updateEnv('DB_PASSWORD', $dbPassword);
            }
        }
        $this->info('  Configuration written.');

        // Clear config cache
        Artisan::call('config:clear');
        $this->info('  Config cache cleared.');

        // Run migrations
        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info('  Migrations complete.');

        // Seed (unless --no-seed)
        if (! $this->option('no-seed')) {
            $this->info('Seeding default content...');
            Artisan::call('db:seed', ['--class' => 'DefaultContentSeeder', '--force' => true]);
            $this->info('  Default content seeded.');

            if (! Schema::hasTable('roles') || \Spatie\Permission\Models\Role::count() === 0) {
                $this->info('Seeding roles and permissions...');
                Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
                $this->info('  Roles seeded.');
            } else {
                $this->info('  Roles already exist, skipping.');
            }
        } else {
            $this->info('Skipping seeders (--no-seed).');
        }

        // Create admin user
        $this->info('Creating admin user...');
        $admin = User::where('email', $adminEmail)->first();

        if ($admin) {
            $this->warn("  User with email '{$adminEmail}' already exists, assigning admin role.");
        } else {
            $admin = User::create([
                'username' => $adminUsername,
                'name' => $adminUsername,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]);
            $this->info("  Admin user '{$adminUsername}' created.");
        }
        $admin->assignRole('admin');

        // Storage link
        Artisan::call('storage:link', [], $this->getOutput());

        // Save forum config
        ForumConfig::set('forum_name', $forumName);
        ForumConfig::set('forum_url', $forumUrl);
        $this->info('  Forum config saved.');

        // Success
        $this->info('');
        $this->info('========================================');
        $this->info('  VoltexaHub installed successfully!');
        $this->info('========================================');
        $this->info('');
        $this->info("  URL:   {$forumUrl}");
        $this->info("  Admin: {$adminEmail}");
        $this->info('');
        $this->info("  Login at: {$forumUrl}/login");
        $this->info('');

        return 0;
    }

    private function updateEnv(string $key, ?string $value): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        // Quote values that contain spaces
        $escapedValue = $value;
        if ($value && (str_contains($value, ' ') || str_contains($value, '#'))) {
            $escapedValue = '"' . $value . '"';
        }

        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $content);
        } else {
            $content .= "\n{$key}={$escapedValue}";
        }

        file_put_contents($envPath, $content);
    }
}
