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
        Schema::table('login_activities', function (Blueprint $table) {
            $table->index('token_id');
            $table->index('status');
            $table->index('is_active');
            $table->index('login_at');

            $table->index(['user_id', 'token_id', 'is_active'], 'user_session_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_activities', function (Blueprint $table) {
            $table->dropIndex(['token_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['login_at']);
            $table->dropIndex('user_session_index');
        });
    }
};
