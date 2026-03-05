<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->unsignedBigInteger('prefix_id')->nullable()->after('subforum_id');
            $table->foreign('prefix_id')->references('id')->on('thread_prefixes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->dropForeign(['prefix_id']);
            $table->dropColumn('prefix_id');
        });
    }
};
