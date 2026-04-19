<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            $table->foreign('last_post_id')->references('id')->on('posts')->nullOnDelete();
        });

        Schema::table('threads', function (Blueprint $table) {
            $table->foreign('last_post_id')->references('id')->on('posts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            $table->dropForeign(['last_post_id']);
        });

        Schema::table('threads', function (Blueprint $table) {
            $table->dropForeign(['last_post_id']);
        });
    }
};
