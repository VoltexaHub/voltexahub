<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->integer('priority')->default(0)->after('guard_name');
            $table->boolean('is_staff')->default(false)->after('priority');
            $table->json('staff_permissions')->nullable()->after('is_staff');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['priority', 'is_staff', 'staff_permissions']);
        });
    }
};
