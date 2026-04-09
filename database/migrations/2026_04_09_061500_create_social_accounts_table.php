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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_id');
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });

        DB::table('users')
            ->whereNotNull('provider')
            ->whereNotNull('provider_id')
            ->orderBy('id')
            ->get(['id', 'provider', 'provider_id'])
            ->each(function ($user) {
                DB::table('social_accounts')->updateOrInsert(
                    [
                        'provider' => $user->provider,
                        'provider_id' => $user->provider_id,
                    ],
                    [
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
