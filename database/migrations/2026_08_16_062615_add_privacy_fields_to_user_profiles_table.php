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
            $table->enum('privacy_profile_activity', ['everyone', 'nobody', 'only_connected'])
                ->default('everyone')
                ->after('skills_id');

            $table->enum('privacy_profile_visibility', ['everyone', 'nobody', 'only_connected'])
                ->default('everyone')
                ->after('privacy_profile_activity');

            // High-traffic performance index
            $table->index('privacy_profile_activity');
            $table->index('privacy_profile_visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex(['privacy_profile_activity']);
            $table->dropIndex(['privacy_profile_visibility']);

            // Drop columns
            $table->dropColumn([
                'privacy_profile_activity',
                'privacy_profile_visibility'
            ]);
        });
    }
};
