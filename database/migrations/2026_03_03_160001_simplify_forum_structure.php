<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_forum_id')->nullable()->after('category_id');
            $table->foreign('parent_forum_id')->references('id')->on('forums')->onDelete('set null');
            $table->index('parent_forum_id');
        });
    }

    public function down(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            $table->dropForeign(['parent_forum_id']);
            $table->dropIndex(['parent_forum_id']);
            $table->dropColumn('parent_forum_id');
        });
    }
};
