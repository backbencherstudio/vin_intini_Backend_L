<?php

declare(strict_types=1);

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
        if (! Schema::hasColumn('login_activities', 'device_token_hash')) {
            Schema::table('login_activities', function (Blueprint $table): void {
                $table->string('device_token_hash', 64)->nullable()->after('token_id')->index();
            });
        }

        if (! Schema::hasTable('refresh_tokens')) {
            Schema::create('refresh_tokens', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('login_activity_id')->nullable()->constrained('login_activities')->cascadeOnDelete();
                $table->uuid('family_id')->index();
                $table->string('token_hash', 64)->unique();
                $table->string('jti', 64)->nullable()->index();
                $table->timestamp('expires_at');
                $table->timestamp('rotated_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('security_audit_logs')) {
            Schema::create('security_audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event', 80)->index();
                $table->string('severity', 20)->default('info');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->string('token_id', 64)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
        Schema::dropIfExists('refresh_tokens');

        if (Schema::hasColumn('login_activities', 'device_token_hash')) {
            Schema::table('login_activities', function (Blueprint $table): void {
                $table->dropColumn('device_token_hash');
            });
        }
    }
};
