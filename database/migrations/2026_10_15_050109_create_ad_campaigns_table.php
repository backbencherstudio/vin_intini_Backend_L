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
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();

            // Polymorphic target (Product, Job, Publication, Post)
            $table->string('boostable_type');
            $table->unsignedBigInteger('boostable_id');

            // Ownership & Relations (Preserved under industry even if creator user account is deleted)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->foreignId('boost_package_id')->nullable()->constrained('boost_packages')->nullOnDelete();

            // Targeting & Placements
            $table->enum('ad_type', ['product', 'job', 'publication', 'post'])->default('product')->index();
            $table->string('network_type', 50)->default('both')->index(); // 'psychology', 'neuroscience', 'both'
            $table->json('placements')->nullable(); // e.g. ["marketplace", "newsfeed"]

            // Financial & Payment Audit
            $table->decimal('amount_paid', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_checkout_session_id')->nullable()->index();

            // Lifecycle with Pause / Clock-Freeze Support
            $table->unsignedInteger('duration_days');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('paused_at')->nullable()->index();
            $table->enum('status', [
                'pending',
                'active',
                'paused',
                'completed',
                'cancelled',
            ])->default('pending')->index();

            // Aggregated Metrics
            $table->unsignedBigInteger('total_impressions')->default(0);
            $table->unsignedBigInteger('total_clicks')->default(0);

            $table->timestamps();

            // Composite indexes for sub-5ms query performance
            $table->index(['boostable_type', 'boostable_id', 'status'], 'idx_campaign_boostable_status');
            $table->index(['ad_type', 'network_type', 'status', 'ends_at'], 'idx_active_ad_feed_lookup');
            $table->index(['industry_id', 'status'], 'idx_industry_campaigns');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
