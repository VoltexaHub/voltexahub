<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upgrade_plans', function (Blueprint $table) {
            $table->decimal('xp_multiplier', 4, 2)->default(1.00)->after('rep_daily_limit');
        });
    }

    public function down(): void
    {
        Schema::table('upgrade_plans', function (Blueprint $table) {
            $table->dropColumn('xp_multiplier');
        });
    }
};
