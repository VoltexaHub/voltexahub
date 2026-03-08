<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('minecraft_username')->nullable()->after('minecraft_ign');
            $table->string('minecraft_uuid')->nullable()->unique()->after('minecraft_username');
            $table->timestamp('minecraft_verified_at')->nullable()->after('minecraft_verified');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['minecraft_username', 'minecraft_uuid', 'minecraft_verified_at']);
        });
    }
};
