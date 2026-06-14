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
        Schema::create('industry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('industry_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('tag')->nullable();
            $table->string('sub_title')->nullable();
            $table->string('indication')->nullable();
            $table->string('moa')->nullable();
            $table->string('pub_date')->nullable();
            $table->string('extra_tag')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industry_items');
    }
};
