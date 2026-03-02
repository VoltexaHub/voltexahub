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
    protected $signature = 'voltexahub:install';
    protected $description = 'Interactive installer for VoltexaHub';

    public function handle(): int
    {
        $this->info('');
        $this->info('========================================');
        $this->info('       VoltexaHub Installer');
        $this->info('========================================');
        $this->info('');

        // Step 1: Check if already installed
        if (Schema::hasTable('users') && User::count() > 0) {
            if (! $this->confirm('VoltexaHub appears to already be installed. Do you want to re-run the installer?')) {
                $this->info('Installation cancelled.');
                return 0;
            }
        }

        // Step 2: Forum name
        $forumName = $this->ask('What is your forum name?', 'My Forum');

        // Step 3: Forum URL
        $forumUrl = $this->ask('What is your forum URL?', 'http://localhost:8000');

        // Step 4-6: Admin details
        $adminUsername = $this->ask('Admin username');
        $adminEmail = $this->ask('Admin email');
        $adminPassword = $this->secret('Admin password');
        $adminPasswordConfirm = $this->secret('Confirm admin password');

        if ($adminPassword !== $adminPasswordConfirm) {
            $this->error('Passwords do not match. Please try again.');
            return 1;
        }

        // Step 7: Database connection
        $dbConnection = $this->choice('Database connection', ['sqlite', 'mysql'], 0);

        $dbHost = $dbPort = $dbDatabase = $dbUsername = $dbPassword = null;
        if ($dbConnection === 'mysql') {
            $dbHost = $this->ask('MySQL host', '127.0.0.1');
            $dbPort = $this->ask('MySQL port', '3306');
            $dbDatabase = $this->ask('Database name', 'voltexahub');
            $dbUsername = $this->ask('Database username', 'root');
            $dbPassword = $this->secret('Database password');
        }

        $this->info('');
        $this->info('Setting up VoltexaHub...');
        $this->info('');

        // Step 8: Generate APP_KEY if not set
        if (empty(env('APP_KEY'))) {
            $this->info('Generating application key...');
            Artisan::call('key:generate', ['--force' => true]);
            $this->info('  Application key generated.');
        }

        // Step 9: Write .env values
        $this->info('Writing configuration...');
        $this->updateEnv('APP_NAME', $forumName);
        $this->updateEnv('APP_URL', $forumUrl);
        $this->updateEnv('DB_CONNECTION', $dbConnection);

        if ($dbConnection === 'mysql') {
            $this->updateEnv('DB_HOST', $dbHost);
            $this->updateEnv('DB_PORT', $dbPort);
            $this->updateEnv('DB_DATABASE', $dbDatabase);
            $this->updateEnv('DB_USERNAME', $dbUsername);
            $this->updateEnv('DB_PASSWORD', $dbPassword);
        }
        $this->info('  Configuration written.');

        // Step 10: Clear config cache
        Artisan::call('config:clear');
        $this->info('  Config cache cleared.');

        // Step 11: Run migrations
        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info('  Migrations complete.');

        // Step 12: Seed default content
        $this->info('Seeding default content...');
        Artisan::call('db:seed', ['--class' => 'DefaultContentSeeder', '--force' => true]);
        $this->info('  Default content seeded.');

        // Step 13: Seed roles (if not already run)
        if (! Schema::hasTable('roles') || \Spatie\Permission\Models\Role::count() === 0) {
            $this->info('Seeding roles and permissions...');
            Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
            $this->info('  Roles seeded.');
        } else {
            $this->info('  Roles already exist, skipping.');
        }

        // Step 14: Create admin user
        $this->info('Creating admin user...');
        $admin = User::create([
            'username' => $adminUsername,
            'name' => $adminUsername,
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');
        $this->info("  Admin user '{$adminUsername}' created.");

        // Step 15: Storage link
        Artisan::call('storage:link', [], $this->getOutput());

        // Step 16: Save forum name to config
        ForumConfig::set('forum_name', $forumName);
        $this->info('  Forum name saved to config.');

        // Step 17: Success
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
