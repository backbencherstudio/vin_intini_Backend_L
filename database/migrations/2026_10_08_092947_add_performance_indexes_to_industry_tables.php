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
        if (! $this->hasIndex('industry_sections', 'idx_sections_network_industry')) {
            Schema::table('industry_sections', function (Blueprint $table) {
                $table->index(['network_type', 'industry_type'], 'idx_sections_network_industry');
            });
        }

        if (! $this->hasIndex('industry_products', 'idx_feed_sort_lookup')) {
            Schema::table('industry_products', function (Blueprint $table) {
                $table->index(['network_type', 'industry_type', 'status', 'id'], 'idx_feed_sort_lookup');
            });
        }

        if (! $this->hasIndex('industry_products', 'idx_section_status_id')) {
            Schema::table('industry_products', function (Blueprint $table) {
                $table->index(['section_id', 'status', 'id'], 'idx_section_status_id');
            });
        }

        if (! $this->hasIndex('industry_products', 'idx_category_status_id')) {
            Schema::table('industry_products', function (Blueprint $table) {
                $table->index(['category_id', 'status', 'id'], 'idx_category_status_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->hasIndex('industry_sections', 'idx_sections_network_industry')) {
            Schema::table('industry_sections', function (Blueprint $table) {
                $table->dropIndex('idx_sections_network_industry');
            });
        }

        Schema::table('industry_products', function (Blueprint $table) {
            if ($this->hasIndex('industry_products', 'idx_feed_sort_lookup')) {
                $table->dropIndex('idx_feed_sort_lookup');
            }
            if ($this->hasIndex('industry_products', 'idx_section_status_id')) {
                $table->dropIndex('idx_section_status_id');
            }
            if ($this->hasIndex('industry_products', 'idx_category_status_id')) {
                $table->index('category_id', 'industry_products_category_id_foreign');
                $table->dropIndex('idx_category_status_id');
            }
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name')->all();

        return in_array($name, $indexes, true);
    }
};
