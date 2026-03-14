<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->enum('type', ['manual', 'achievement', 'purchasable'])->default('manual')->after('icon_path');
            $table->unsignedBigInteger('achievement_id')->nullable()->after('type');
            $table->unsignedInteger('price_credits')->nullable()->after('achievement_id');
            $table->decimal('price_money', 8, 2)->nullable()->after('price_credits');
            $table->unsignedInteger('display_order')->default(0)->after('price_money');

            $table->foreign('achievement_id')->references('id')->on('achievements')->nullOnDelete();
        });

        // Make granted_by nullable on user_awards
        // Drop the existing FK, alter the column, then re-add FK as nullable
        Schema::table('user_awards', function (Blueprint $table) {
            $table->dropForeign(['granted_by']);
        });

        DB::statement('ALTER TABLE user_awards MODIFY granted_by BIGINT UNSIGNED NULL');

        Schema::table('user_awards', function (Blueprint $table) {
            $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_awards', function (Blueprint $table) {
            $table->dropForeign(['granted_by']);
        });

        DB::statement('ALTER TABLE user_awards MODIFY granted_by BIGINT UNSIGNED NOT NULL');

        Schema::table('user_awards', function (Blueprint $table) {
            $table->foreign('granted_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('awards', function (Blueprint $table) {
            $table->dropForeign(['achievement_id']);
            $table->dropColumn(['type', 'achievement_id', 'price_credits', 'price_money', 'display_order']);
        });
    }
};
