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
        Schema::table('replies', function (Blueprint $table) {
            $table->unsignedBigInteger('reply_to_user_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('reply_to_reply_id')->nullable()->after('reply_to_user_id');

            $table->foreign('reply_to_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('reply_to_reply_id')
                ->references('id')
                ->on('replies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->dropForeign(['reply_to_user_id']);
            $table->dropForeign(['reply_to_reply_id']);
            $table->dropColumn(['reply_to_user_id', 'reply_to_reply_id']);
        });
    }
};
