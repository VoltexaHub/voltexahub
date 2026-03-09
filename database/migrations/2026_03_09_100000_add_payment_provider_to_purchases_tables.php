<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_purchases', function (Blueprint $table) {
            $table->string('payment_provider')->default('stripe')->after('payment_method');
        });

        Schema::table('upgrade_purchases', function (Blueprint $table) {
            $table->string('payment_provider')->default('stripe')->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('store_purchases', function (Blueprint $table) {
            $table->dropColumn('payment_provider');
        });

        Schema::table('upgrade_purchases', function (Blueprint $table) {
            $table->dropColumn('payment_provider');
        });
    }
};
