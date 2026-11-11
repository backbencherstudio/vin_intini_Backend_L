<?php

declare(strict_types=1);

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
        Schema::create('academia_post_baccalaureate_program_category', function (Blueprint $table): void {
            $table->foreignId('program_id')
                ->constrained('academia_post_baccalaureate_programs')
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('academia_post_baccalaureate_categories')
                ->cascadeOnDelete();

            $table->primary(['program_id', 'category_id'], 'post_bacc_program_category_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academia_post_baccalaureate_program_category');
    }
};
