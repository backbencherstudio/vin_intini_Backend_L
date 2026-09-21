<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('industry_job_posts', function (Blueprint $table) {
            $table->string('job_id', 20)->unique()->nullable()->after('id');

            $table->string('slug')->unique()->nullable()->after('job_title');

            $table->dropColumn(['state', 'city']);

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

            $table->dropForeign(['created_by']);
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('industry_job_posts', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn(['state_id', 'city_id']);

            $table->string('state')->nullable()->after('employment_type');
            $table->string('city')->nullable()->after('state');

            $table->dropColumn([
                'job_id',
                'network_type',
                'slug',
                'website',
            ]);
        });
    }
};
