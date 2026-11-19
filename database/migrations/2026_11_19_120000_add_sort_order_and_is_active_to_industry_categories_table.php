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
        Schema::table('industry_categories', function (Blueprint $table): void {
            if (!Schema::hasColumn('industry_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('category_name');
            }
            if (!Schema::hasColumn('industry_categories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });

        // Initialize sort_order per section sequentially
        if (Schema::hasColumn('industry_categories', 'sort_order')) {
            $sections = DB::table('industry_sections')->pluck('id');
            foreach ($sections as $sectionId) {
                $categories = DB::table('industry_categories')
                    ->where('section_id', $sectionId)
                    ->orderBy('id', 'asc')
                    ->get();
                foreach ($categories as $index => $cat) {
                    DB::table('industry_categories')
                        ->where('id', $cat->id)
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
        Schema::table('industry_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('industry_categories', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('industry_categories', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
