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
        Schema::create('academia_medical_residencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
            $table->string('program_name');
            $table->string('location')->nullable();
            $table->json('degree_types')->nullable(); // Store as ["MD", "DO"]

            // GPS Location
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academia_medical_residencies');
    }
};
