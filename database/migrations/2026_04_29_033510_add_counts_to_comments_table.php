<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedInteger('like_count')->default(0)->after('comment');
            $table->unsignedInteger('reply_count')->default(0)->after('like_count');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn([
                'like_count',
                'reply_count',
                'reply_like_count'
            ]);
        });
    }
};
