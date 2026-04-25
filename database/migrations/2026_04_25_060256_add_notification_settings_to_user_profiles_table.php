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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->boolean('notify_jobs')->default(false);
            $table->boolean('notify_publications')->default(false);
            $table->boolean('notify_residency')->default(false);
            $table->boolean('notify_offers')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['notify_jobs', 'notify_publications', 'notify_residency', 'notify_offers']);
        });
    }
};
