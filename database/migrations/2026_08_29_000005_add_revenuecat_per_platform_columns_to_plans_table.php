<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('revenuecat_store_identifier_ios')->nullable()->after('revenuecat_store_identifier');
            $table->string('revenuecat_store_identifier_android')->nullable()->after('revenuecat_store_identifier_ios');
            $table->string('revenuecat_product_id_ios')->nullable()->after('revenuecat_product_id');
            $table->string('revenuecat_product_id_android')->nullable()->after('revenuecat_product_id_ios');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'revenuecat_store_identifier_ios',
                'revenuecat_store_identifier_android',
                'revenuecat_product_id_ios',
                'revenuecat_product_id_android',
            ]);
        });
    }
};
