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
        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->string('revenuecat_transaction_id')->nullable()->index()->after('stripe_checkout_session_id');
            $table->string('payment_platform', 30)->default('stripe')->after('revenuecat_transaction_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('ad_campaign_id')->nullable()->after('subscription_id')->constrained('ad_campaigns')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ad_campaign_id');
        });

        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropColumn(['revenuecat_transaction_id', 'payment_platform']);
        });
    }
};
