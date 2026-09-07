<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename(
            'recruiter_posts',
            'industry_posts'
        );

        Schema::rename(
            'recruiter_post_comments',
            'industry_post_comments'
        );

        Schema::rename(
            'recruiter_post_likes',
            'industry_post_likes'
        );

        Schema::rename(
            'recruiter_comment_likes',
            'industry_comment_likes'
        );

        Schema::rename(
            'recruiter_post_media',
            'industry_post_media'
        );
    }

    public function down(): void
    {
        Schema::rename(
            'industry_posts',
            'recruiter_posts'
        );

        Schema::rename(
            'industry_post_comments',
            'recruiter_post_comments'
        );

        Schema::rename(
            'industry_post_likes',
            'recruiter_post_likes'
        );

        Schema::rename(
            'industry_comment_likes',
            'recruiter_comment_likes'
        );

        Schema::rename(
            'industry_post_media',
            'recruiter_post_media'
        );
    }
};
