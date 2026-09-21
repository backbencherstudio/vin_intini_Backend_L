<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('revenuecat_entitlement_identifier')->nullable()->after('revenuecat_store_identifier_android');

            $table->dropColumn([
                'revenuecat_product_id',
                'revenuecat_product_id_ios',
                'revenuecat_product_id_android',
                'revenuecat_entitlement_id',
                'revenuecat_offering_id',
                'revenuecat_package_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('revenuecat_entitlement_identifier');

            $table->string('revenuecat_product_id')->nullable()->after('stripe_price_id');
            $table->string('revenuecat_entitlement_id')->nullable()->after('revenuecat_product_id');
            $table->string('revenuecat_product_id_ios')->nullable()->after('revenuecat_product_id');
            $table->string('revenuecat_product_id_android')->nullable()->after('revenuecat_product_id_ios');
            $table->string('revenuecat_offering_id')->nullable()->after('revenuecat_entitlement_id');
            $table->string('revenuecat_package_id')->nullable()->after('revenuecat_offering_id');
        });
    }
};
