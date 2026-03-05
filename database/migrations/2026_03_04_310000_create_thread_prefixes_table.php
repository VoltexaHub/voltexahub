<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thread_prefixes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('color', 20)->default('#7c3aed');
            $table->string('bg_color', 20)->default('#7c3aed1a');
            $table->string('text_color', 20)->default('#a78bfa');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thread_prefixes');
    }
};
