<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruiter_post_comments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('post_id')
                ->constrained('recruiter_posts')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('recruiter_post_comments')
                ->cascadeOnDelete();

            $table->text('comment')
                ->nullable();

            $table->string('image')
                ->nullable();

            $table->unsignedInteger('likes_count')
                ->default(0);

            $table->timestamps();

            $table->index([
                'post_id',
                'parent_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_post_comments');
    }
};
