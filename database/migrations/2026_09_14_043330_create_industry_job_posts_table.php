<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_job_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('industry_id')
                ->constrained('industries')
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('job_title');

            $table->text('job_description');

            $table->string('work_mode');
            $table->string('employment_type');

            $table->string('state')->nullable();
            $table->string('city')->nullable();

            $table->string('email');

            $table->string('phone_number')->nullable();

            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();

            $table->string('location_url', 1000)->nullable();

            $table->string('employment_offering')->nullable();

            $table->json('tags')->nullable();

            $table->date('announcement_start_date')->nullable();
            $table->date('announcement_end_date')->nullable();

            $table->string('status')->default('draft');

            $table->boolean('information_confirmed')
                ->default(false);

            $table->timestamp('submitted_at')->nullable();

            $table->timestamp('reviewed_at')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index('industry_id');
            $table->index('created_by');
            $table->index('status');
            $table->index([
                'industry_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_job_posts');
    }
};
