<?php

declare(strict_types=1);

use App\Models\IntegrationSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            'pusher_app_id' => config('broadcasting.connections.pusher.app_id') ?: (env('PUSHER_APP_ID') ?? ''),
            'pusher_app_key' => config('broadcasting.connections.pusher.key') ?: (env('PUSHER_APP_KEY') ?? ''),
            'pusher_app_secret' => config('broadcasting.connections.pusher.secret') ?: (env('PUSHER_APP_SECRET') ?? ''),
            'pusher_app_cluster' => config('broadcasting.connections.pusher.options.cluster') ?: (env('PUSHER_APP_CLUSTER') ?? 'mt1'),
        ];

        foreach ($defaults as $key => $value) {
            IntegrationSetting::firstOrCreate(
                ['key' => $key],
                ['value' => (string) $value, 'section' => 'Pusher']
            );
        }

        IntegrationSetting::where('key', 'apple_client_id_ios')->where('section', '')->update(['section' => 'Apple OAuth']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        IntegrationSetting::where('section', 'Pusher')->delete();
    }
};
