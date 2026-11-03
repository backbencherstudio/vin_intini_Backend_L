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
        Schema::create('user_job_preferences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('job_title')->nullable();
            $table->string('position')->nullable();
            $table->string('network_type')->nullable();
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('employment_offering')->nullable();
            $table->string('work_mode')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('level')->nullable();
            $table->string('experience')->nullable();
            $table->string('salary_type')->nullable();
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();

            $table->foreignId('state_id')
                ->nullable()
                ->constrained('states')
                ->nullOnDelete();

            $table->foreignId('city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('work_mode');
            $table->index('employment_type');
            $table->index('network_type');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_job_preferences');
    }
};

