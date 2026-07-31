<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('provider_transaction_id')->nullable()->index();
            $table->string('checkout_session_id')->nullable()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('usd');
            $table->string('card_brand')->nullable();
            $table->string('card_last4')->nullable();
            $table->string('status')->default('pending'); // pending, succeeded, failed, refunded
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
