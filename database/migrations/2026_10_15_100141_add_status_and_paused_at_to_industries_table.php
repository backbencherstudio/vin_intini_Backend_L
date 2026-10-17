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
        Schema::table('industries', function (Blueprint $table) {
            if (! Schema::hasColumn('industries', 'status')) {
                $table->string('status')->default('active')->after('authorization_confirmed_at');
                $table->index('status');
            }

            if (! Schema::hasColumn('industries', 'paused_at')) {
                $table->timestamp('paused_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            if (Schema::hasColumn('industries', 'paused_at')) {
                $table->dropColumn('paused_at');
            }

            if (Schema::hasColumn('industries', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};
