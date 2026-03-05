<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locked_content_reports', function (Blueprint $table) {
            $table->id();
            $table->string('content_hash', 64)->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['working', 'not_working']);
            $table->timestamps();
            $table->unique(['content_hash', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locked_content_reports');
    }
};
