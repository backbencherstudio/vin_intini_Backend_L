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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->json('profession')->nullable(); // Multi-select array data
            $table->string('highest_degree')->nullable();
            $table->string('study_category')->nullable(); // static category
            $table->string('field_study')->nullable(); // static sub-category
            $table->string('institution')->nullable();
            $table->string('graduation_year')->nullable();
            $table->json('interests')->nullable(); // Multi-select array data
            $table->text('about')->nullable();
            $table->json('skills_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
