<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('user_title')->nullable()->after('username');
            $table->text('bio')->nullable()->after('user_title');
            $table->text('signature')->nullable()->after('bio');
            $table->string('avatar_color', 7)->default('#7c3aed')->after('signature');
            $table->integer('credits')->default(0)->after('avatar_color');
            $table->integer('post_count')->default(0)->after('credits');
            $table->boolean('is_online')->default(false)->after('post_count');
            $table->timestamp('last_active_at')->nullable()->after('is_online');
            $table->string('discord_username')->nullable()->after('last_active_at');
            $table->string('twitter_handle')->nullable()->after('discord_username');
            $table->string('website_url')->nullable()->after('twitter_handle');
            $table->string('minecraft_ign')->nullable()->after('website_url');
            $table->boolean('minecraft_verified')->default(false)->after('minecraft_ign');
            $table->string('rust_steam_id')->nullable()->after('minecraft_verified');
            $table->boolean('rust_verified')->default(false)->after('rust_steam_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'user_title', 'bio', 'signature', 'avatar_color',
                'credits', 'post_count', 'is_online', 'last_active_at',
                'discord_username', 'twitter_handle', 'website_url',
                'minecraft_ign', 'minecraft_verified', 'rust_steam_id', 'rust_verified',
            ]);
        });
    }
};
