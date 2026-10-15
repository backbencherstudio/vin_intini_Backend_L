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
        Schema::create('boost_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('target_entity', [
                'all',
                'product',
                'job',
                'publication',
                'post',
            ])->default('product')->index();
            $table->unsignedInteger('duration_days');
            $table->decimal('price', 8, 2);
            $table->string('estimated_impressions')->nullable();
            $table->string('badge')->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('priority_weight')->default(1);
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();

            $table->index(['target_entity', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boost_packages');
    }
};
