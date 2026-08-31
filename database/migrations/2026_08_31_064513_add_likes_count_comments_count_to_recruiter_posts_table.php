<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruiter_posts', function (Blueprint $table) {

            // CHANGE: Add total likes count
            $table->unsignedInteger('likes_count')
                ->default(0)
                ->after('content');

            // CHANGE: Add total comments count
            $table->unsignedInteger('comments_count')
                ->default(0)
                ->after('likes_count');
        });
    }

    public function down(): void
    {
        Schema::table('recruiter_posts', function (Blueprint $table) {
            $table->dropColumn([
                'likes_count',
                'comments_count',
            ]);
        });
    }
};
