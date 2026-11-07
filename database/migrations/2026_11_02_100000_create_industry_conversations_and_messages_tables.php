<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('industry_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('industry_last_read_at')->nullable();
            $table->timestamp('user_last_read_at')->nullable();
            $table->timestamp('industry_archived_at')->nullable();
            $table->timestamp('user_archived_at')->nullable();
            $table->timestamp('industry_deleted_at')->nullable();
            $table->timestamp('user_deleted_at')->nullable();
            $table->unsignedBigInteger('industry_cleared_message_id')->nullable();
            $table->unsignedBigInteger('user_cleared_message_id')->nullable();
            $table->timestamps();

            $table->unique(['industry_id', 'user_id']);
            $table->foreign('industry_id')->references('id')->on('industries')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('industry_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('industry_conversation_id');
            $table->string('sender_type', 20)->default('user'); // 'user' or 'industry'
            $table->unsignedBigInteger('sender_id'); // User who created the message
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->string('type', 20)->default('text'); // 'text', 'voice', 'file', 'image', 'video'
            $table->text('message')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('industry_conversation_id');
            $table->index('sender_id');
            $table->index('created_at');

            $table->foreign('industry_conversation_id')->references('id')->on('industry_conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reply_to_id')->references('id')->on('industry_messages')->nullOnDelete();
        });

        Schema::table('industry_conversations', function (Blueprint $table) {
            $table->foreign('last_message_id')->references('id')->on('industry_messages')->nullOnDelete();
        });

        Schema::create('industry_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('industry_message_id');
            $table->unsignedBigInteger('user_id');
            $table->string('reaction', 50);
            $table->timestamps();

            $table->unique(['industry_message_id', 'user_id']);
            $table->foreign('industry_message_id')->references('id')->on('industry_messages')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_message_reactions');

        Schema::table('industry_conversations', function (Blueprint $table) {
            $table->dropForeign(['last_message_id']);
        });

        Schema::dropIfExists('industry_messages');
        Schema::dropIfExists('industry_conversations');
    }
};
