<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service'); // forum, database, websocket, queue
            $table->enum('status', ['operational', 'degraded', 'outage']);
            $table->string('message')->nullable();
            $table->boolean('is_override')->default(false);
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['service', 'checked_at']);
            $table->index('is_override');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_checks');
    }
};
