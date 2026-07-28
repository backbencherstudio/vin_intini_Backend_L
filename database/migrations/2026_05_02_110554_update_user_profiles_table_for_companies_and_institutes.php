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
        DB::table('user_profiles')->update(['current_position_id' => null]);

        Schema::table('user_profiles', function (Blueprint $table) {
            try {
                $table->dropForeign(['current_position_id']);
            } catch (Exception $e) {
            }

            $table->foreign('current_position_id')
                ->references('id')
                ->on('companies')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['current_position_id']);

            $table->foreign('current_position_id')
                ->references('id')
                ->on('experiences')
                ->onDelete('set null');
        });
    }
};
