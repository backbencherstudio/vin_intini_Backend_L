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
        Schema::table('industry_products', function (Blueprint $table) {
            // New Release tracking (24h free boost)
            $table->boolean('is_new_release')->default(false)->after('status')->index();
            $table->timestamp('new_release_expires_at')->nullable()->after('is_new_release')->index();

            // Paid Boost tracking
            $table->boolean('is_boosted')->default(false)->after('new_release_expires_at')->index();
            $table->timestamp('boosted_at')->nullable()->after('is_boosted');
            $table->timestamp('boost_expires_at')->nullable()->after('boosted_at')->index();
            $table->unsignedInteger('boost_score')->default(0)->after('boost_expires_at');

            // Metric Counters
            $table->unsignedBigInteger('impressions_count')->default(0)->after('views_count');
            $table->unsignedBigInteger('clicks_count')->default(0)->after('impressions_count');

            // Composite index for ultra-fast Marketplace Feed queries
            $table->index(['network_type', 'status', 'is_boosted', 'boost_score', 'id'], 'idx_products_boost_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industry_products', function (Blueprint $table) {
            $table->dropIndex('idx_products_boost_sort');
            $table->dropColumn([
                'is_new_release',
                'new_release_expires_at',
                'is_boosted',
                'boosted_at',
                'boost_expires_at',
                'boost_score',
                'impressions_count',
                'clicks_count',
            ]);
        });
    }
};
