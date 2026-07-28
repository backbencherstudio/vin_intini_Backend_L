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
        Schema::table('industry_partners', function (Blueprint $table) {
            $table->string('partner_logo')->nullable()->after('partner_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industry_partners', function (Blueprint $table) {
            $table->dropColumn('partner_logo');
        });
    }
};
