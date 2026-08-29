<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('revenuecat_product_id')->nullable()->after('stripe_price_id');
            $table->string('revenuecat_entitlement_id')->nullable()->after('revenuecat_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['revenuecat_product_id', 'revenuecat_entitlement_id']);
        });
    }
};
