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
        Schema::table('boost_packages', function (Blueprint $table): void {
            $table->json('placements')->nullable()->after('target_entity');
            $table->unsignedInteger('sort_order')->default(0)->after('priority_weight')->index();
        });

        DB::table('boost_packages')
            ->whereNull('placements')
            ->update([
                'placements' => json_encode(['product_feed', 'newsfeed']),
            ]);

        $ids = DB::table('boost_packages')
            ->orderBy('duration_days')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $index => $id) {
            DB::table('boost_packages')
                ->where('id', $id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boost_packages', function (Blueprint $table): void {
            $table->dropColumn(['placements', 'sort_order']);
        });
    }
};
