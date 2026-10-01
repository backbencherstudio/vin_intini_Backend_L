<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_industry', function (Blueprint $table) {
            $table->id();

            // A post may appear on at most one company page, so the post side
            // of the mapping is unique. A plain `post_groups` style pair unique
            // index would allow a single post to be listed on several
            // companies and silently corrupt the company page queries.
            $table->foreignId('post_id')->unique()->constrained('posts')->cascadeOnDelete();
            $table->foreignId('industry_id')->constrained('industries')->cascadeOnDelete();

            $table->timestamps();
        });

        // Only present when upgrading from the `posts.industry_id` column
        // version; a fresh install creates `posts` without it.
        if (Schema::hasColumn('posts', 'industry_id')) {
            $this->migrateColumnToMapping();

            Schema::table('posts', function (Blueprint $table) {
                // The foreign key borrows the leading `industry_id` column of
                // the composite index, so it goes before that index.
                $table->dropForeign(['industry_id']);
                $table->dropIndex(['industry_id', 'created_at']);
                $table->dropColumn('industry_id');
            });
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->enum('visibility', [
                'public',
                'connections',
                'groups',
                'followers',
                'private',
            ])->default('public')->change();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('industry_id')
                ->nullable()
                ->after('user_id')
                ->constrained('industries')
                ->cascadeOnDelete();

            $table->index(['industry_id', 'created_at']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->enum('visibility', ['public', 'connections', 'groups'])
                ->default('public')->change();
        });

        if (Schema::hasTable('post_industry')) {
            $this->migrateMappingToColumn();
        }

        Schema::dropIfExists('post_industry');
    }

    /**
     * Copy `posts.industry_id` into the mapping table, preserving timestamps.
     */
    private function migrateColumnToMapping(): void
    {
        DB::table('posts')
            ->select(['id', 'industry_id', 'created_at', 'updated_at'])
            ->whereNotNull('industry_id')
            ->orderBy('id')
            ->chunkById(200, function ($posts) {
                $rows = [];

                foreach ($posts as $post) {
                    $rows[] = [
                        'post_id' => $post->id,
                        'industry_id' => $post->industry_id,
                        'created_at' => $post->created_at,
                        'updated_at' => $post->updated_at,
                    ];
                }

                DB::table('post_industry')->insert($rows);
            }, 'id');
    }

    /**
     * Copy the mapping table back into `posts.industry_id`.
     */
    private function migrateMappingToColumn(): void
    {
        DB::table('post_industry')
            ->select(['post_id', 'industry_id'])
            ->orderBy('post_id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('posts')
                        ->where('id', $row->post_id)
                        ->update(['industry_id' => $row->industry_id]);
                }
            }, 'post_id');
    }
};
