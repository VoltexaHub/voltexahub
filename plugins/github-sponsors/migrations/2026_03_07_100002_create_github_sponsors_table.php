<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_sponsors', function (Blueprint $table) {
            $table->id();
            $table->string('github_login')->index();
            $table->string('tier')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('sponsored_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_sponsors');
    }
};
