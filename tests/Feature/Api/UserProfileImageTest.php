<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserProfileImageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $this->user = User::factory()->create([
            'is_verified' => true,
            'profile_image' => null,
            'cover_image' => null,
        ]);
        $this->user->assignRole($role);

        UserProfile::create([
            'user_id' => $this->user->id,
            'country' => 'Bangladesh',
        ]);

        Storage::fake('public');
    }

    public function test_profile_image_can_be_updated(): void
    {
        $response = $this
            ->actingAs($this->user, 'api')
            ->postJson('/api/profile/images', [
                'profile_image' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $freshUser = $this->user->fresh();

        $this->assertNotNull($freshUser->profile_image);
        $this->assertSame(
            $freshUser->profile_image,
            $response->json('data.profile_image'),
        );
        Storage::disk('public')->assertExists($freshUser->profile_image);
    }

    public function test_cover_image_can_be_updated(): void
    {
        $response = $this
            ->actingAs($this->user, 'api')
            ->postJson('/api/profile/images', [
                'cover_image' => UploadedFile::fake()->image('cover.png'),
            ]);

        $response->assertOk();

        $this->assertNotNull($this->user->fresh()->cover_image);
    }

    public function test_previous_profile_image_is_replaced(): void
    {
        $this->actingAs($this->user, 'api')
            ->postJson('/api/profile/images', [
                'profile_image' => UploadedFile::fake()->image('first.jpg'),
            ]);

        $firstPath = $this->user->fresh()->profile_image;

        $this->actingAs($this->user, 'api')
            ->postJson('/api/profile/images', [
                'profile_image' => UploadedFile::fake()->image('second.jpg'),
            ]);

        $secondPath = $this->user->fresh()->profile_image;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_request_without_any_image_fails_validation(): void
    {
        $response = $this
            ->actingAs($this->user, 'api')
            ->postJson('/api/profile/images', []);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $response = $this
            ->actingAs($this->user, 'api')
            ->postJson('/api/profile/images', [
                'profile_image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422);

        $this->assertNull($this->user->fresh()->profile_image);
    }
}
