<?php

declare(strict_types=1);

use Database\Seeders\PostBaccalaureateCategorySeeder;
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
        Schema::create('academia_post_baccalaureate_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        (new PostBaccalaureateCategorySeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academia_post_baccalaureate_categories');
    }
};
