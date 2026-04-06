<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('logo')->nullable();
            $table->string('cover_photo')->nullable();
            $table->json('industry')->nullable(); // Multi-select up to 3
            $table->string('location')->nullable();
            $table->text('rules')->nullable();

            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');

            // Group Type & Discoverability (Enums for validation)
            $table->enum('type', ['public', 'private'])->default('public');
            $table->enum('discoverability', ['listed', 'unlisted'])->default('listed');

            // Permissions (Boolean checkboxes)
            $table->boolean('allow_member_invites')->default(true);
            $table->boolean('require_post_approval')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
