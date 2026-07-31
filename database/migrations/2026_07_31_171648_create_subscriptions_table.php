<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('platform')->default('stripe'); // stripe, ios, android
            $table->string('provider_subscription_id')->nullable()->index();
            $table->string('provider_customer_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('price_id')->nullable();
            $table->string('status')->default('active'); // active, trialing, past_due, canceled, unpaid, incomplete, incomplete_expired, paused, suspended
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_subscription_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
