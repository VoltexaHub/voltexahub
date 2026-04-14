<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('oauth_provider')->nullable()->after('is_admin');
            $table->string('oauth_provider_id')->nullable()->after('oauth_provider');
            $table->string('oauth_avatar')->nullable()->after('oauth_provider_id');
            $table->string('password')->nullable()->change();

            $table->unique(['oauth_provider', 'oauth_provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['oauth_provider', 'oauth_provider_id']);
            $table->dropColumn(['oauth_provider', 'oauth_provider_id', 'oauth_avatar']);
        });
    }
};
