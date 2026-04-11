<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ProfileImageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileImageServiceTest extends TestCase
{
    public function test_store_from_url_downloads_and_stores_image(): void
    {
        Storage::fake('public');

        Http::fake([
            'https://example.com/*' => Http::response('fake-image-bytes', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $service = new ProfileImageService();
        $path = $service->storeFromUrl('https://example.com/avatar.jpg');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_profile_image_url_returns_remote_url_when_value_is_url(): void
    {
        config(['app.url' => 'http://localhost']);

        $user = new User([
            'profile_image' => 'https://cdn.example.com/avatar.png',
        ]);

        $this->assertSame('https://cdn.example.com/avatar.png', $user->profile_image_url);
    }

    public function test_profile_image_url_returns_storage_asset_for_local_path(): void
    {
        $user = new User([
            'profile_image' => 'profile_photos/avatar.png',
        ]);

        $this->assertSame(asset('storage/profile_photos/avatar.png'), $user->profile_image_url);
    }
}
