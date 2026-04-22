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
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('total_page', 1)
            ->assertJsonPath('stats.total_notifications', 1)
            ->assertJsonPath('stats.unread_notifications', 1)
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

    public function test_user_can_get_realtime_config(): void
    {
        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');
        config()->set('broadcasting.connections.pusher.options.cluster', 'mt1');
        config()->set('broadcasting.connections.pusher.options.host', 'api-mt1.pusher.com');
        config()->set('broadcasting.connections.pusher.options.port', 443);
        config()->set('broadcasting.connections.pusher.options.scheme', 'https');

        $user = $this->makeUser();

        $response = $this->actingAs($user, 'api')->getJson('/api/notifications/realtime-config');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.broadcaster', 'pusher')
            ->assertJsonPath('data.pusher.key', 'test-key')
            ->assertJsonPath('data.pusher.cluster', 'mt1')
            ->assertJsonPath('data.auth_endpoint', url('/api/broadcasting/auth'))
            ->assertJsonPath('data.user_channel', 'App.Models.User.{id}')
            ->assertJsonPath('data.notifications.received', 'App\\Notifications\\ConnectionRequestReceivedNotification')
            ->assertJsonPath('data.notifications.accepted', 'App\\Notifications\\ConnectionRequestAcceptedNotification')
            ->assertJsonPath('data.api.list', url('/api/notifications'))
            ->assertJsonPath('data.api.unread_count', url('/api/notifications/unread-count'));
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
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('total_page', 1)
            ->assertJsonPath('filters.unread_only', true)
            ->assertJsonPath('stats.total_notifications', 2)
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
