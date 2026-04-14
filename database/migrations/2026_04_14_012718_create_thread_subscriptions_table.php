<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('thread_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('thread_id')->constrained()->cascadeOnDelete();
            $table->string('state', 16);  // 'subscribed' or 'muted'
            $table->timestamps();

            $table->unique(['user_id', 'thread_id']);
            $table->index(['thread_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thread_subscriptions');
    }
};
