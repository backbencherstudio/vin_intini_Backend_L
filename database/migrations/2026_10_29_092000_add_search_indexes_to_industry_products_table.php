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
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            if (! $this->hasIndex('industry_products', 'idx_products_name_fulltext')) {
                Schema::table('industry_products', function (Blueprint $table) {
                    $table->fullText('product_name', 'idx_products_name_fulltext');
                });
            }

            if (! $this->hasIndex('industry_products', 'idx_products_name')) {
                Schema::table('industry_products', function (Blueprint $table) {
                    $table->index('product_name', 'idx_products_name');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('industry_products', function (Blueprint $table) {
                if ($this->hasIndex('industry_products', 'idx_products_name_fulltext')) {
                    $table->dropIndex('idx_products_name_fulltext');
                }
                if ($this->hasIndex('industry_products', 'idx_products_name')) {
                    $table->dropIndex('idx_products_name');
                }
            });
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name')->all();

        return in_array($name, $indexes, true);
    }
};
