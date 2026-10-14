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
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('industry_id')
                ->nullable()
                ->after('user_id')
                ->constrained('industries')
                ->nullOnDelete();
        });

        Schema::table('replies', function (Blueprint $table) {
            $table->foreignId('industry_id')
                ->nullable()
                ->after('user_id')
                ->constrained('industries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->dropForeign(['industry_id']);
            $table->dropColumn('industry_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['industry_id']);
            $table->dropColumn('industry_id');
        });
    }
};
