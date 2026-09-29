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
        Schema::table('industry_job_posts', function (Blueprint $table) {
            $table->string('category')->nullable()->after('position');
            $table->string('sub_category')->nullable()->after('category');
            $table->string('salary_type')->nullable()->after('salary_max');

            $table->index('category');
            $table->index('sub_category');
        });
    }

    public function down(): void
    {
        Schema::table('industry_job_posts', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['sub_category']);
            $table->dropColumn(['category', 'sub_category', 'salary_type']);
        });
    }
};
