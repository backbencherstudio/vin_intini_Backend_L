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
        Schema::create('industry_job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_id', 10)->unique();

            $table->foreignId('job_id')
                ->constrained('industry_job_posts')
                ->cascadeOnDelete();

            $table->foreignId('applicant_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('application_type', ['custom', 'quick'])->default('custom');

            // Form Fields based on UI
            $table->string('full_name');
            $table->string('email');
            $table->string('phone_number')->nullable();
            $table->string('experiences')->nullable();
            $table->string('current_position')->nullable();
            $table->decimal('expected_salary', 12, 2);
            $table->string('location')->nullable();

            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();

            $table->text('cover_letter')->nullable();
            $table->text('about_yourself')->nullable();
            $table->json('skills')->nullable();

            $table->string('resume_path');

            $table->string('status')->default('pending'); // pending, shortlisted, rejected, hired

            $table->timestamps();

            // Indexes
            $table->index('application_id');
            $table->index(['job_id', 'applicant_id']);
            $table->unique(['job_id', 'applicant_id']); // Ek user ek post e ekbar apply korte parbe
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industry_job_applications');
    }
};
