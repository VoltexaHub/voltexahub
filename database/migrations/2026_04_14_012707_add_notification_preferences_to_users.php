<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_reply_email')->default(true);
            $table->boolean('notify_reply_app')->default(true);
            $table->boolean('notify_pm_email')->default(true);
            $table->boolean('notify_pm_app')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_reply_email', 'notify_reply_app', 'notify_pm_email', 'notify_pm_app']);
        });
    }
};
