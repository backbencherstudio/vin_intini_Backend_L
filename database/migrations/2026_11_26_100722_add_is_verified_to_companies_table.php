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
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->index()->after('name');
        });

        // Keep existing companies visible in public suggestions.
        DB::table('companies')->update(['is_verified' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);
            $table->dropColumn('is_verified');
        });
    }
};
