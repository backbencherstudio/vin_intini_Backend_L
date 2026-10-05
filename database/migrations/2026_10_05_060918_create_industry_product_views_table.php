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
        Schema::create('industry_product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_product_id')
                ->constrained('industry_products')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['industry_product_id', 'user_id'], 'uniq_product_user_view');
            $table->index(['industry_product_id', 'ip_address'], 'idx_product_ip_view');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industry_product_views');
    }
};
