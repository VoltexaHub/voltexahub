<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('header_color', 7)->nullable()->after('description'); // e.g. #7c3aed
            if (Schema::hasColumn('categories', 'header_image')) {
                $table->dropColumn('header_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('header_color');
            $table->string('header_image')->nullable()->after('description');
        });
    }
};
