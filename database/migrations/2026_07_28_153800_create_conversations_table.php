<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id_1');
            $table->unsignedBigInteger('user_id_2');
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('user_1_last_read_at')->nullable();
            $table->timestamp('user_2_last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id_1', 'user_id_2']);

            $table->foreign('user_id_1')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('user_id_2')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
