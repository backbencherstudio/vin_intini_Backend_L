<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company posts now live in the shared `posts` table, keyed by a nullable
     * `posts.industry_id`. The dedicated industry post tables are dropped.
     */
    private array $tables = [
        'industry_comment_likes',
        'industry_post_likes',
        'industry_post_comments',
        'industry_post_media',
        'industry_posts',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        Schema::create('industry_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->string('visibility')->default('public');
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();

            $table->index(['industry_id', 'created_at']);
        });

        Schema::create('industry_post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_post_id')->constrained('industry_posts')->cascadeOnDelete();
            $table->enum('type', ['image', 'video']);
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['industry_post_id', 'type']);
        });

        Schema::create('industry_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('industry_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['post_id', 'user_id']);
        });

        Schema::create('industry_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('industry_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()
                ->constrained('industry_post_comments')->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();

            $table->index(['post_id', 'parent_id']);
        });

        Schema::create('industry_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')
                ->constrained('industry_post_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['comment_id', 'user_id']);
        });
    }
};
