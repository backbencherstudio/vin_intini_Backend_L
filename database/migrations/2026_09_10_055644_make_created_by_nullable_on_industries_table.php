<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            $table->dropForeign(['created_by']);

            // Make created_by nullable and set NULL when user is permanently deleted
            $table->foreignId('created_by')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            $table->dropForeign(['created_by']);

            // Restore the original behavior
            $table->foreignId('created_by')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();
        });
    }
};
