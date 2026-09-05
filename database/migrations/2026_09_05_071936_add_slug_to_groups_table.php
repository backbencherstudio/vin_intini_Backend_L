<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Group;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        Group::chunk(100, function ($groups) {
            foreach ($groups as $group) {
                $baseSlug = Str::slug($group->name);

                if (empty($baseSlug)) {
                    $baseSlug = 'group-' . $group->id;
                }

                $finalSlug = $baseSlug;
                $counter = 1;

                while (Group::where('slug', $finalSlug)->exists()) {
                    $finalSlug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $group->slug = $finalSlug;
                $group->save();
            }
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
