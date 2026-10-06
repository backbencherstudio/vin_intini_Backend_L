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
        Schema::create('industry_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id', 10)->nullable()->unique();

            // Ownership & Association
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('industry_id')
                ->constrained('industries')
                ->cascadeOnDelete();

            // Categorization
            $table->string('network_type'); // 'psychology', 'neuroscience'
            $table->string('industry_type'); // 'biotechnology', 'psychotropics'
            $table->foreignId('section_id')
                ->constrained('industry_sections')
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('industry_categories')
                ->cascadeOnDelete();

            // Product Details
            $table->string('product_name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('product_url', 1000);
            $table->string('image')->nullable();
            $table->json('tags')->nullable();

            // Person of Contact (POC)
            $table->string('poc_name')->nullable();
            $table->string('poc_email')->nullable();
            $table->string('poc_phone')->nullable();

            $table->boolean('information_confirmed')->default(true);

            // Status & Counters
            $table->string('status')->default('active'); // 'active', 'inactive', 'draft'
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // High-Volume Compound Indexes
            $table->index(['network_type', 'industry_type', 'section_id', 'status'], 'idx_feed_section_lookup');
            $table->index(['section_id', 'category_id', 'status'], 'idx_subcategory_lookup');
            $table->index(['industry_id', 'status'], 'idx_industry_dashboard');
            $table->index(['creator_id', 'status'], 'idx_creator_products');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industry_products');
    }
};
