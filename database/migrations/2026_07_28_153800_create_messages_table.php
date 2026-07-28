<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->string('type', 20)->default('text');
            $table->text('message')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('conversation_id');
            $table->index('sender_id');
            $table->index('created_at');

            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('last_message_id')->references('id')->on('messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['last_message_id']);
        });

        Schema::dropIfExists('messages');
    }
};
