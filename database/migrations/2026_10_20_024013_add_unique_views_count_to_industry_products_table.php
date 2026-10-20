<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('industry_products', function (Blueprint $table) {
            $table->unsignedBigInteger('unique_views_count')->default(0)->after('views_count');
        });

        DB::statement('UPDATE industry_products SET unique_views_count = views_count WHERE unique_views_count = 0 AND views_count > 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industry_products', function (Blueprint $table) {
            $table->dropColumn('unique_views_count');
        });
    }
};
