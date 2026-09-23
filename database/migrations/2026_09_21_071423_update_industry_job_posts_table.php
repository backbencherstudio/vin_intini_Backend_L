<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('industry_job_posts', function (Blueprint $table) {
            // New columns
            $table->string('job_id', 20)->unique()->nullable()->after('id');
            $table->string('slug')->unique()->nullable()->after('job_title');
            $table->string('position')->nullable()->after('slug');

            // Drop old columns
            $table->dropColumn(['state', 'city']);

            // Add foreign keys for state and city
            $table->foreignId('state_id')
                ->nullable()
                ->after('employment_type')
                ->constrained('states')
                ->nullOnDelete();

            $table->foreignId('city_id')
                ->nullable()
                ->after('state_id')
                ->constrained('cities')
                ->nullOnDelete();

            $table->string('network_type')->nullable()->after('employment_offering');
            $table->string('website')->nullable()->after('salary_max');
            $table->string('level')->nullable()->after('employment_type');
            $table->string('experience')->nullable()->after('level');

            $table->unsignedBigInteger('views_count')->default(0)->after('status');
            $table->unsignedBigInteger('likes_count')->default(0)->after('views_count');

            // Add new indexes
            $table->index(['status', 'id']);
            $table->index('work_mode');
            $table->index('employment_type');
            $table->index('employment_offering');
            $table->index('network_type');

            // Drop existing foreign key on created_by
            $table->dropForeign(['created_by']);
        });

        // Separate schema block to safely change created_by column & add foreign key
        Schema::table('industry_job_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // 1. Drop foreign key on created_by first
        Schema::table('industry_job_posts', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        // 2. Revert created_by back to NOT NULL and re-add original cascade foreign key
        Schema::table('industry_job_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->dropIndex(['status', 'id']);
            $table->dropIndex(['work_mode']);
            $table->dropIndex(['employment_type']);
            $table->dropIndex(['employment_offering']);
            $table->dropIndex(['network_type']);

            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn(['state_id', 'city_id']);

            $table->string('state')->nullable()->after('employment_type');
            $table->string('city')->nullable()->after('state');

            $table->dropColumn([
                'job_id',
                'network_type',
                'slug',
                'position',
                'level',
                'experience',
                'website',
                'views_count',
                'likes_count',
            ]);
        });
    }
};
