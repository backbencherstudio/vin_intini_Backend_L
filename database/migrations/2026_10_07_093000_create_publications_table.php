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
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('publication_id', 10)->nullable()->unique();

            // Ownership & Association
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('industry_id')
                ->nullable()
                ->constrained('industries')
                ->cascadeOnDelete();

            // Categorization
            $table->string('network_type'); // 'psychology', 'neuroscience'
            $table->string('publication_type'); // 'professional', 'university', 'freelance', 'other'

            // Publication Details
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('authors')->nullable();
            $table->text('abstract');
            $table->string('website_url', 1000);
            $table->string('attachment')->nullable();

            $table->boolean('information_confirmed')->default(true);

            // Status & Counters
            $table->string('status')->default('active'); // 'active', 'inactive', 'draft'
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // High-Volume Compound Indexes
            $table->index(['network_type', 'publication_type', 'status'], 'idx_pub_feed_lookup');
            $table->index(['industry_id', 'status'], 'idx_pub_industry');
            $table->index(['creator_id', 'status'], 'idx_pub_creator');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
