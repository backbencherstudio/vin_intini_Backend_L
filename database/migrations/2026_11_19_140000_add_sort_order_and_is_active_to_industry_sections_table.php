<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('industry_sections', function (Blueprint $table): void {
            if (!Schema::hasColumn('industry_sections', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('name');
            }
            if (!Schema::hasColumn('industry_sections', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });

        // Initialize sort_order per (network_type, industry_type) sequentially
        if (Schema::hasColumn('industry_sections', 'sort_order')) {
            $groups = DB::table('industry_sections')
                ->select('network_type', 'industry_type')
                ->distinct()
                ->get();

            foreach ($groups as $group) {
                $sections = DB::table('industry_sections')
                    ->where('network_type', $group->network_type)
                    ->where('industry_type', $group->industry_type)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($sections as $index => $sec) {
                    DB::table('industry_sections')
                        ->where('id', $sec->id)
                        ->update(['sort_order' => $index + 1]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industry_sections', function (Blueprint $table): void {
            if (Schema::hasColumn('industry_sections', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('industry_sections', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
