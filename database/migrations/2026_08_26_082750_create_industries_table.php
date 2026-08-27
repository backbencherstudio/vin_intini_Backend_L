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
        Schema::create('industries', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->foreignId('industry_category_id')
                ->constrained('industry_categories')
                ->restrictOnDelete();

            $table->string('website')->nullable();
            $table->text('address')->nullable();

            $table->string('company_size')->nullable();

            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();

            $table->string('tagline')->nullable();

            $table->boolean('authorization_confirmed')
                ->default(false);

            $table->timestamp('authorization_confirmed_at')
                ->nullable();

            $table->foreignId('created_by')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industries');
    }
};
