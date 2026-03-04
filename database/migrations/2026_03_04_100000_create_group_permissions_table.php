<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role_name')->unique();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_post')->default(true);
            $table->boolean('can_reply')->default(true);
            $table->timestamps();
        });

        // Alter forum_permissions to use nullable booleans (null = inherit from group)
        Schema::table('forum_permissions', function (Blueprint $table) {
            $table->boolean('can_view')->nullable()->default(null)->change();
            $table->boolean('can_post')->nullable()->default(null)->change();
            $table->boolean('can_reply')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_permissions');
        Schema::table('forum_permissions', function (Blueprint $table) {
            $table->boolean('can_view')->default(true)->change();
            $table->boolean('can_post')->default(true)->change();
            $table->boolean('can_reply')->default(true)->change();
        });
    }
};
