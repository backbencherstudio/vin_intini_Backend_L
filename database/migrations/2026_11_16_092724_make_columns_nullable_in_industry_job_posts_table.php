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
            $table->text('job_description')->nullable()->change();
            $table->string('work_mode')->nullable()->change();
            $table->string('employment_type')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industry_job_posts', function (Blueprint $table) {
            $table->text('job_description')->nullable(false)->change();
            $table->string('work_mode')->nullable(false)->change();
            $table->string('employment_type')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
