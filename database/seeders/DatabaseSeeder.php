<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Refusing to seed demo data in production.');
            $this->command?->line('  Create your admin account manually, e.g.:');
            $this->command?->line('    php artisan tinker --execute="\App\Models\User::create([');
            $this->command?->line('        \'name\' => \'Admin\',');
            $this->command?->line('        \'handle\' => \'admin\',');
            $this->command?->line('        \'email\' => \'you@example.com\',');
            $this->command?->line('        \'password\' => bcrypt(\'a-real-strong-password\'),');
            $this->command?->line('        \'email_verified_at\' => now(),');
            $this->command?->line('        \'is_admin\' => true,');
            $this->command?->line('    ]);"');

            return;
        }

        User::factory()->create([
            'name' => 'Admin',
            'handle' => 'admin',
            'email' => 'admin@voltexahub.test',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'handle' => 'test',
            'email' => 'test@example.com',
        ]);

        $this->call(ForumSeeder::class);
    }
}
