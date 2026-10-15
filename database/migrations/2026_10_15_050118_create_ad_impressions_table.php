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
        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->string('boostable_type', 100);
            $table->unsignedBigInteger('boostable_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('placement', 50); // e.g. 'marketplace', 'newsfeed', 'search'
            $table->string('ip_address', 45)->nullable();
            $table->date('viewed_date')->index();
            $table->timestamp('created_at')->useCurrent();

            // Anti-fraud duplicate debounce constraint per session/day
            $table->index(['ad_campaign_id', 'user_id', 'viewed_date'], 'idx_impression_unique_check');
            $table->index(['boostable_type', 'boostable_id', 'created_at'], 'idx_entity_impressions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_impressions');
    }
};
