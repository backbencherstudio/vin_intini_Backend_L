<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            // Make existing created_by nullable
            $table->unsignedBigInteger('created_by')
                ->nullable()
                ->change();

            // Add foreign key with ON DELETE SET NULL
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            $table->dropForeign(['created_by']);

            $table->unsignedBigInteger('created_by')
                ->nullable(false)
                ->change();
        });
    }
};
