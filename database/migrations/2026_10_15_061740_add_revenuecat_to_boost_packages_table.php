<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('boost_packages', function (Blueprint $table) {
            $table->string('revenuecat_store_identifier_ios')->nullable()->after('features');
            $table->string('revenuecat_store_identifier_android')->nullable()->after('revenuecat_store_identifier_ios');
            $table->string('revenuecat_entitlement_identifier')->nullable()->after('revenuecat_store_identifier_android');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boost_packages', function (Blueprint $table) {
            $table->dropColumn([
                'revenuecat_store_identifier_ios',
                'revenuecat_store_identifier_android',
                'revenuecat_entitlement_identifier',
            ]);
        });
    }
};
