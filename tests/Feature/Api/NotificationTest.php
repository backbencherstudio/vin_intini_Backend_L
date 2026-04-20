<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_their_notifications(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        // Create a connection request which triggers notification
        $this->actingAs($sender, 'api')->postJson('/api/connections/request', [
            'user_id' => $receiver->id,
        ]);

        // Get notifications
        $response = $this->actingAs($receiver, 'api')->getJson('/api/notifications');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data');

        $notification = $response->json('data')[0];
        $this->assertSame('App\Notifications\ConnectionRequestReceivedNotification', $notification['type']);
        $this->assertFalse($notification['is_read']);
    }

    public function test_user_can_get_unread_count(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $this->actingAs($sender, 'api')->postJson('/api/connections/request', [
            'user_id' => $receiver->id,
        ]);

        $response = $this->actingAs($receiver, 'api')->getJson('/api/notifications/unread-count');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $this->actingAs($sender, 'api')->postJson('/api/connections/request', [
            'user_id' => $receiver->id,
        ]);

        $notification = $receiver->notifications()->first();

        $response = $this->actingAs($receiver, 'api')->postJson("/api/notifications/{$notification->id}/mark-as-read");

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.is_read', true);

        $this->assertNotNull($receiver->notifications()->first()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        // Create multiple notifications
        for ($i = 0; $i < 3; $i++) {
            $newSender = $this->makeUser();
            $this->actingAs($newSender, 'api')->postJson('/api/connections/request', [
                'user_id' => $receiver->id,
            ]);
        }

        $response = $this->actingAs($receiver, 'api')->postJson('/api/notifications/mark-all-as-read');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.updated_count', 3);
    }

    public function test_user_can_delete_notification(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $this->actingAs($sender, 'api')->postJson('/api/connections/request', [
            'user_id' => $receiver->id,
        ]);

        $notification = $receiver->notifications()->first();

        $response = $this->actingAs($receiver, 'api')->deleteJson("/api/notifications/{$notification->id}");

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertCount(0, $receiver->notifications);
    }

    public function test_user_can_delete_all_notifications(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        for ($i = 0; $i < 3; $i++) {
            $newSender = $this->makeUser();
            $this->actingAs($newSender, 'api')->postJson('/api/connections/request', [
                'user_id' => $receiver->id,
            ]);
        }

        $response = $this->actingAs($receiver, 'api')->deleteJson('/api/notifications');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.deleted_count', 3);
    }

    public function test_user_can_filter_unread_notifications(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        for ($i = 0; $i < 3; $i++) {
            $newSender = $this->makeUser();
            $this->actingAs($newSender, 'api')->postJson('/api/connections/request', [
                'user_id' => $receiver->id,
            ]);
        }

        // Mark one as read
        $notifications = $receiver->notifications()->get();
        $notifications->first()->update(['read_at' => now()]);

        $response = $this->actingAs($receiver, 'api')->getJson('/api/notifications?unread_only=true');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.unread_only', true)
            ->assertJsonCount(2, 'data');
    }

    private function makeUser(?string $firstName = null, ?string $lastName = null): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
            'first_name' => $firstName ?? fake()->firstName(),
            'last_name' => $lastName ?? fake()->lastName(),
            'title' => fake()->jobTitle(),
        ]);

        $user->assignRole($role);
        UserProfile::create([
            'user_id' => $user->id,
        ]);

        return $user;
    }
}
