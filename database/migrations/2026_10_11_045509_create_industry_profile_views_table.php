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
        Schema::create('industry_profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained('industries')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['industry_id', 'created_at']);
            $table->index(['industry_id', 'user_id']);
        });

        if (! Schema::hasColumn('industries', 'views_count')) {
            Schema::table('industries', function (Blueprint $table) {
                $table->unsignedBigInteger('views_count')->default(0)->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industry_profile_views');

        if (Schema::hasColumn('industries', 'views_count')) {
            Schema::table('industries', function (Blueprint $table) {
                $table->dropColumn('views_count');
            });
        }
    }
};
