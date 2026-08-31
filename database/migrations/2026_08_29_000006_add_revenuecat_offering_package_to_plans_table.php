<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('revenuecat_offering_id')->nullable()->after('revenuecat_entitlement_id');
            $table->string('revenuecat_package_id')->nullable()->after('revenuecat_offering_id');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['revenuecat_offering_id', 'revenuecat_package_id']);
        });
    }
};
