<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('github_username')->nullable()->after('rust_steam_id');
            $table->boolean('is_sponsor')->default(false)->after('github_username');
            $table->timestamp('sponsor_since')->nullable()->after('is_sponsor');
            $table->string('sponsor_tier')->nullable()->after('sponsor_since');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['github_username', 'is_sponsor', 'sponsor_since', 'sponsor_tier']);
        });
    }
};
