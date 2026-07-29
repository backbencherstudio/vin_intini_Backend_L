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
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('user_1_archived_at')->nullable()->after('user_2_last_read_at');
            $table->timestamp('user_2_archived_at')->nullable()->after('user_1_archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['user_1_archived_at', 'user_2_archived_at']);
        });
    }
};
