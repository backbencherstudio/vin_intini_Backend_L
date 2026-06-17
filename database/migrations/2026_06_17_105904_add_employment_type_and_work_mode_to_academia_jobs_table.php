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
        Schema::table('academia_jobs', function (Blueprint $table) {
            $table->string('employment_type')->nullable()->after('category');
            $table->string('work_mode')->nullable()->after('employment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academia_jobs', function (Blueprint $table) {
            $table->dropColumn(['employment_type', 'work_mode']);
        });
    }
};
