<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            $table->dropForeign(['industry_category_id']);
        });

        Schema::table('industries', function (Blueprint $table) {
            $table->renameColumn('industry_category_id', 'industry');
        });

        Schema::table('industries', function (Blueprint $table) {
            $table->string('industry', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            $table->unsignedBigInteger('industry')->nullable()->change();
        });

        Schema::table('industries', function (Blueprint $table) {
            $table->renameColumn('industry', 'industry_category_id');
        });

        Schema::table('industries', function (Blueprint $table) {
            $table->foreign('industry_category_id')
                ->references('id')
                ->on('industry_categories')
                ->onDelete('cascade');
        });
    }
};
