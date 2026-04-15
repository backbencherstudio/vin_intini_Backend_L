<?php

namespace Tests\Feature\Api;

use App\Models\ConnectionRequest;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserConnectionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_connection_request_and_it_starts_pending(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $response = $this->actingAs($sender, 'api')->postJson('/api/connections/request', [
            'user_id' => $receiver->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection request sent successfully.')
            ->assertJsonPath('data.status', ConnectionRequest::STATUS_PENDING)
            ->assertJsonPath('data.user.id', $receiver->id)
            ->assertJsonPath('data.is_outgoing', true);

        $this->assertDatabaseHas('connection_requests', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequest::STATUS_PENDING,
        ]);
    }

    public function test_user_can_accept_connection_request_and_creates_mutual_follows(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $connectionRequest = ConnectionRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($receiver, 'api')->postJson('/api/connections/requests/' . $connectionRequest->id . '/accept');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection request accepted successfully.')
            ->assertJsonPath('data.status', ConnectionRequest::STATUS_ACCEPTED)
            ->assertJsonPath('data.is_incoming', true);

        $this->assertDatabaseHas('connection_requests', [
            'id' => $connectionRequest->id,
            'status' => ConnectionRequest::STATUS_ACCEPTED,
        ]);

        $this->assertDatabaseHas('user_follows', [
            'follower_id' => $sender->id,
            'following_id' => $receiver->id,
        ]);

        $this->assertDatabaseHas('user_follows', [
            'follower_id' => $receiver->id,
            'following_id' => $sender->id,
        ]);
    }

    public function test_user_can_ignore_connection_request(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $connectionRequest = ConnectionRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($receiver, 'api')->postJson('/api/connections/requests/' . $connectionRequest->id . '/ignore');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection request ignored successfully.')
            ->assertJsonPath('data.status', ConnectionRequest::STATUS_IGNORED)
            ->assertJsonPath('data.can_accept', false)
            ->assertJsonPath('data.can_ignore', false);

        $this->assertDatabaseHas('connection_requests', [
            'id' => $connectionRequest->id,
            'status' => ConnectionRequest::STATUS_IGNORED,
        ]);

        $this->assertDatabaseMissing('user_follows', [
            'follower_id' => $sender->id,
            'following_id' => $receiver->id,
        ]);
    }

    public function test_user_can_view_followers_following_and_unfollow(): void
    {
        $firstUser = $this->makeUser();
        $secondUser = $this->makeUser();

        ConnectionRequest::create([
            'sender_id' => $firstUser->id,
            'receiver_id' => $secondUser->id,
            'status' => ConnectionRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        UserFollow::create([
            'follower_id' => $firstUser->id,
            'following_id' => $secondUser->id,
        ]);

        UserFollow::create([
            'follower_id' => $secondUser->id,
            'following_id' => $firstUser->id,
        ]);

        $followersResponse = $this->actingAs($firstUser, 'api')->getJson('/api/connections/followers');

        $followersResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $secondUser->id)
            ->assertJsonPath('data.0.is_following_back', true);

        $followingResponse = $this->actingAs($firstUser, 'api')->getJson('/api/connections/following');

        $followingResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $secondUser->id)
            ->assertJsonPath('data.0.is_followed_back', true);

        $unfollowResponse = $this->actingAs($firstUser, 'api')->deleteJson('/api/connections/' . $secondUser->id . '/unfollow');

        $unfollowResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Unfollowed successfully.')
            ->assertJsonPath('data.is_following', false);

        $this->assertDatabaseMissing('user_follows', [
            'follower_id' => $firstUser->id,
            'following_id' => $secondUser->id,
        ]);

        $refollowResponse = $this->actingAs($firstUser, 'api')->postJson('/api/connections/' . $secondUser->id . '/follow');

        $refollowResponse
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Followed successfully.')
            ->assertJsonPath('data.user.id', $secondUser->id)
            ->assertJsonPath('data.is_following', true);

        $this->assertDatabaseHas('user_follows', [
            'follower_id' => $firstUser->id,
            'following_id' => $secondUser->id,
        ]);
    }

    public function test_user_can_view_connection_request_history(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();
        $acceptedSender = $this->makeUser();
        $ignoredSender = $this->makeUser();

        ConnectionRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequest::STATUS_PENDING,
        ]);

        ConnectionRequest::create([
            'sender_id' => $acceptedSender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        ConnectionRequest::create([
            'sender_id' => $ignoredSender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequest::STATUS_IGNORED,
            'responded_at' => now(),
        ]);

        $response = $this->actingAs($receiver, 'api')->getJson('/api/connections/requests');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', ConnectionRequest::STATUS_PENDING)
            ->assertJsonPath('data.0.message', $sender->first_name . ' ' . $sender->last_name . ' sent you a connection request')
            ->assertJsonPath('data.0.can_accept', true)
            ->assertJsonPath('data.0.can_ignore', true);

        $this->assertDatabaseCount('connection_requests', 3);
    }

    public function test_user_can_remove_connection_and_it_removes_mutual_follows(): void
    {
        $firstUser = $this->makeUser();
        $secondUser = $this->makeUser();

        $connectionRequest = ConnectionRequest::create([
            'sender_id' => $firstUser->id,
            'receiver_id' => $secondUser->id,
            'status' => ConnectionRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        UserFollow::create([
            'follower_id' => $firstUser->id,
            'following_id' => $secondUser->id,
        ]);

        UserFollow::create([
            'follower_id' => $secondUser->id,
            'following_id' => $firstUser->id,
        ]);

        $response = $this->actingAs($firstUser, 'api')->deleteJson('/api/connections/' . $secondUser->id . '/remove');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection removed successfully.')
            ->assertJsonPath('data.user.id', $secondUser->id)
            ->assertJsonPath('data.is_connected', false)
            ->assertJsonPath('data.is_following', false);

        $this->assertDatabaseMissing('connection_requests', [
            'id' => $connectionRequest->id,
        ]);

        $this->assertDatabaseMissing('user_follows', [
            'follower_id' => $firstUser->id,
            'following_id' => $secondUser->id,
        ]);

        $this->assertDatabaseMissing('user_follows', [
            'follower_id' => $secondUser->id,
            'following_id' => $firstUser->id,
        ]);
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'title' => fake()->jobTitle(),
        ]);

        $user->assignRole($role);
        UserProfile::create([
            'user_id' => $user->id,
        ]);

        return $user;
    }
}
