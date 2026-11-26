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
        Schema::table('skills', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->index()->after('name');
        });

        // Keep existing skills visible in public suggestions.
        DB::table('skills')->update(['is_verified' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);
            $table->dropColumn('is_verified');
        });
    }
};
