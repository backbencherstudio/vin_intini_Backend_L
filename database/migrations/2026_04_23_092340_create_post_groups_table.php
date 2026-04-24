<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('remove_status')->default(0);

            $table->timestamps();
            $table->unique(['post_id', 'group_id']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_groups');
    }
};
