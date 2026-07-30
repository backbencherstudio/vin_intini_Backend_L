<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('short_description')->nullable();
            $table->decimal('billing_rate', 10, 2);
            $table->string('billing_cycle'); // monthly, yearly
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->date('discount_duration')->nullable();
            $table->string('badge_color')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->json('features');
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
