<?php

namespace Tests\Feature\Api;

use App\Models\Connection;
use App\Models\Conversation;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserProfile;
use App\Services\OptimizedImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OptimizedImageUploadTest extends TestCase
{
    public function test_jpeg_upload_is_reencoded_to_webp(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $path = app(OptimizedImageUploadService::class)->store($file, 'uploads');

        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringStartsWith('uploads/', $path);
        Storage::disk('public')->assertExists($path);

        $contents = Storage::disk('public')->get($path);
        $this->assertSame('RIFF', substr($contents, 0, 4));
        $this->assertSame('WEBP', substr($contents, 8, 4));
    }

    public function test_png_upload_is_reencoded_to_webp(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.png', 500, 500);

        $path = app(OptimizedImageUploadService::class)->store($file, 'uploads');

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_svg_upload_is_stored_as_is(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>'
        );

        $path = app(OptimizedImageUploadService::class)->store($file, 'partners');

        $this->assertStringEndsWith('.svg', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('<svg', Storage::disk('public')->get($path));
    }

    public function test_non_image_file_is_stored_as_is(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->createWithContent('document.pdf', 'pdf-bytes');

        $path = app(OptimizedImageUploadService::class)->store($file, 'conversations/1');

        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_message_image_attachment_is_optimized(): void
    {
        Notification::fake();
        Storage::fake('public');

        [$user, $connectedUser] = [$this->makeUser(), $this->makeUser()];
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $response = $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'file',
            'file' => UploadedFile::fake()->image('holiday.jpg', 1200, 900),
        ]);

        $response->assertCreated();

        $message = $conversation->messages()->latest('id')->first();

        $this->assertNotNull($message->file_path);
        $this->assertStringEndsWith('.webp', $message->file_path);
        $this->assertStringStartsWith('conversations/'.$conversation->id.'/', $message->file_path);
        Storage::disk('public')->assertExists($message->file_path);
    }

    private function connectUsers(User $a, User $b): void
    {
        Connection::create([
            'sender_id' => $a->id,
            'receiver_id' => $b->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        UserFollow::create(['follower_id' => $a->id, 'following_id' => $b->id]);
        UserFollow::create(['follower_id' => $b->id, 'following_id' => $a->id]);
    }

    private function makeUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
        ]);
        $user->assignRole($role);
        UserProfile::create(['user_id' => $user->id]);

        return $user;
    }
}
