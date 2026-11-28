<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_audit_logs', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at', 'id'], 'idx_audit_user_created_id');
            $table->index(['severity', 'created_at'], 'idx_audit_severity_created');
        });

        Schema::table('refresh_tokens', function (Blueprint $table): void {
            $table->index('rotated_at', 'idx_refresh_rotated_at');
            $table->index('revoked_at', 'idx_refresh_revoked_at');
            $table->index('expires_at', 'idx_refresh_expires_at');
        });

        Schema::table('login_activities', function (Blueprint $table): void {
            $table->index(['is_active', 'updated_at'], 'idx_login_active_updated');
        });
    }

    public function down(): void
    {
        Schema::table('login_activities', function (Blueprint $table): void {
            $table->dropIndex('idx_login_active_updated');
        });

        Schema::table('refresh_tokens', function (Blueprint $table): void {
            $table->dropIndex('idx_refresh_rotated_at');
            $table->dropIndex('idx_refresh_revoked_at');
            $table->dropIndex('idx_refresh_expires_at');
        });

        Schema::table('security_audit_logs', function (Blueprint $table): void {
            $table->dropIndex('idx_audit_user_created_id');
            $table->dropIndex('idx_audit_severity_created');
        });
    }
};
