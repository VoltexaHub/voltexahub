<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_achievements', function (Blueprint $table) {
            $table->integer('progress')->default(0)->change();
        });
    }

    public function down(): void
    {
        // No rollback needed — this just ensures the default is set
    }
};
