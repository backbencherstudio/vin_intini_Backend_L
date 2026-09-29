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
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type']);
        });

        Schema::dropIfExists('password_otps');
        Schema::dropIfExists('subscription_otps');
        Schema::dropIfExists('registration_otps');
        Schema::dropIfExists('recovery_otps');
        Schema::dropIfExists('recovery_email_otps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');

        Schema::create('password_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('registration_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('recovery_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('recovery_email_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
};
