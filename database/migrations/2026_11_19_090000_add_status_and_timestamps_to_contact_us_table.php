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
        Schema::table('contact_us', function (Blueprint $table) {
            $table->string('status', 20)->default('unread')->after('address')->index();
            $table->timestamp('read_at')->nullable()->after('status');
            $table->timestamp('replied_at')->nullable()->after('read_at');
            $table->text('admin_note')->nullable()->after('replied_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_us', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'read_at', 'replied_at', 'admin_note']);
        });
    }
};
