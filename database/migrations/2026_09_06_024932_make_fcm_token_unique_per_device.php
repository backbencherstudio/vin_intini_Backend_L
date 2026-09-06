<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DELETE t1 FROM fcm_tokens t1 INNER JOIN fcm_tokens t2 ON t1.fcm_token = t2.fcm_token AND t1.id < t2.id');

        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->unique('fcm_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->dropUnique(['fcm_token']);
        });
    }
};
