<?php

namespace Tests\Feature\Api;

use App\Events\ConnectionRemoved;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserProfile;
use App\Notifications\ConnectionRequestAcceptedNotification;
use App\Notifications\ConnectionRequestReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
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
            ->assertJsonPath('data.status', Connection::STATUS_PENDING)
            ->assertJsonPath('data.user.id', $receiver->id)
            ->assertJsonPath('data.is_outgoing', true);

        $this->assertDatabaseHas('connections', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => Connection::STATUS_PENDING,
        ]);
    }

    public function test_sending_connection_request_creates_database_notification_for_receiver(): void
    {
        Notification::fake();

        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $response = $this->actingAs($sender, 'api')->postJson('/api/connections/request', [
            'user_id' => $receiver->id,
        ]);

        $response->assertCreated();

        Notification::assertSentTo(
            $receiver,
            ConnectionRequestReceivedNotification::class,
            function (ConnectionRequestReceivedNotification $notification, array $channels) use ($sender, $receiver): bool {
                $this->assertContains('database', $channels);
                $this->assertContains('broadcast', $channels);
                $this->assertSame($sender->id, $notification->sender->id);
                $this->assertSame($receiver->id, $notification->connectionRequest->receiver_id);

                return true;
            }
        );

        Notification::assertCount(1);
    }

    public function test_connection_request_notification_skips_broadcast_when_pusher_credentials_are_missing(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $connectionRequest = Connection::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id');
        config()->set('broadcasting.connections.pusher.key');
        config()->set('broadcasting.connections.pusher.secret');

        $notification = new ConnectionRequestReceivedNotification($connectionRequest, $sender);

        $this->assertSame(['database'], $notification->via($receiver));
    }

    public function test_user_can_accept_connection_request_and_creates_mutual_follows(): void
    {
        Notification::fake();

        config()->set('broadcasting.default', 'pusher');
        config()->set('broadcasting.connections.pusher.app_id', 'test-app-id');
        config()->set('broadcasting.connections.pusher.key', 'test-key');
        config()->set('broadcasting.connections.pusher.secret', 'test-secret');

        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $connectionRequest = Connection::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($receiver, 'api')->postJson('/api/connections/requests/'.$connectionRequest->id.'/accept');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection request accepted successfully.')
            ->assertJsonPath('data.status', Connection::STATUS_ACCEPTED)
            ->assertJsonPath('data.is_incoming', true);

        $this->assertDatabaseHas('connections', [
            'id' => $connectionRequest->id,
            'status' => Connection::STATUS_ACCEPTED,
        ]);

        $this->assertDatabaseHas('user_follows', [
            'follower_id' => $sender->id,
            'following_id' => $receiver->id,
        ]);

        $this->assertDatabaseHas('user_follows', [
            'follower_id' => $receiver->id,
            'following_id' => $sender->id,
        ]);

        Notification::assertSentTo(
            $sender,
            ConnectionRequestAcceptedNotification::class,
            function (ConnectionRequestAcceptedNotification $notification, array $channels) use ($sender, $receiver): bool {
                $this->assertContains('database', $channels);
                $this->assertContains('broadcast', $channels);
                $this->assertSame($receiver->id, $notification->acceptor->id);
                $this->assertSame($sender->id, $notification->connectionRequest->sender_id);

                return true;
            }
        );
    }

    public function test_user_can_ignore_connection_request(): void
    {
        $sender = $this->makeUser();
        $receiver = $this->makeUser();

        $connectionRequest = Connection::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($receiver, 'api')->postJson('/api/connections/requests/'.$connectionRequest->id.'/ignore');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection request ignored successfully.');

        $this->assertDatabaseMissing('connections', [
            'id' => $connectionRequest->id,
        ]);

        // User can send a new request after ignoring the previous one
        $newResponse = $this->actingAs($sender, 'api')->postJson('/api/connections/request', [
            'user_id' => $receiver->id,
        ]);

        $newResponse
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection request sent successfully.');

        $this->assertDatabaseHas('connections', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => Connection::STATUS_PENDING,
        ]);
    }

    public function test_user_can_view_followers_following_and_unfollow(): void
    {
        $firstUser = $this->makeUser();
        $secondUser = $this->makeUser();
        $mutualUser = $this->makeUser();

        Connection::create([
            'sender_id' => $firstUser->id,
            'receiver_id' => $secondUser->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        UserFollow::create([
            'follower_id' => $firstUser->id,
            'following_id' => $secondUser->id,
        ]);

        UserFollow::create([
            'follower_id' => $firstUser->id,
            'following_id' => $mutualUser->id,
        ]);

        UserFollow::create([
            'follower_id' => $secondUser->id,
            'following_id' => $firstUser->id,
        ]);

        UserFollow::create([
            'follower_id' => $secondUser->id,
            'following_id' => $mutualUser->id,
        ]);

        $followersResponse = $this->actingAs($firstUser, 'api')->getJson('/api/connections/followers');

        $followersResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Followers retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total_followers', 1)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('limit', 1)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total_page', 1)
            ->assertJsonPath('last_page', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $secondUser->id)
            ->assertJsonPath('data.0.is_following_back', true)
            ->assertJsonPath('data.0.mutual_connections_count', 1)
            ->assertJsonPath('data.0.mutual_connections.0.id', $mutualUser->id);

        $followersSearchResponse = $this->actingAs($firstUser, 'api')->getJson('/api/connections/followers?search='.strtolower($secondUser->first_name));

        $followersSearchResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Followers retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total_followers', 1)
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $secondUser->id);

        $followersNoMatchSearchResponse = $this->actingAs($firstUser, 'api')->getJson('/api/connections/followers?search=not-found-name');

        $followersNoMatchSearchResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'No followers found for this search.')
            ->assertJsonPath('total_followers', 1)
            ->assertJsonPath('total', 0)
            ->assertJsonPath('total_page', 0)
            ->assertJsonCount(0, 'data');

        $followingResponse = $this->actingAs($firstUser, 'api')->getJson('/api/connections/following');

        $followingResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Following users retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total_following', 2)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('limit', 2)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total_page', 1)
            ->assertJsonPath('last_page', 1)
            ->assertJsonCount(2, 'data');

        $followingSearchResponse = $this->actingAs($firstUser, 'api')->getJson('/api/connections/following?search='.strtolower($secondUser->first_name));

        $followingSearchResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Following users retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total_following', 2)
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $secondUser->id);

        $followingNoMatchSearchResponse = $this->actingAs($firstUser, 'api')->getJson('/api/connections/following?search=not-found-name');

        $followingNoMatchSearchResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'No following users found for this search.')
            ->assertJsonPath('total_following', 2)
            ->assertJsonPath('total', 0)
            ->assertJsonPath('total_page', 0)
            ->assertJsonCount(0, 'data');

        $followingItems = collect($followingResponse->json('data'));
        $followedItem = $followingItems->first(fn (array $item): bool => (int) $item['user']['id'] === $secondUser->id);

        $this->assertNotNull($followedItem);
        $this->assertTrue($followedItem['is_followed_back']);
        $this->assertSame(1, $followedItem['mutual_connections_count']);
        $this->assertSame($mutualUser->id, $followedItem['mutual_connections'][0]['id']);

        $unfollowResponse = $this->actingAs($firstUser, 'api')->deleteJson('/api/connections/'.$secondUser->id.'/unfollow');

        $unfollowResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Unfollowed successfully.')
            ->assertJsonPath('data.is_following', false);

        $this->assertDatabaseMissing('user_follows', [
            'follower_id' => $firstUser->id,
            'following_id' => $secondUser->id,
        ]);

        $refollowResponse = $this->actingAs($firstUser, 'api')->postJson('/api/connections/'.$secondUser->id.'/follow');

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

        Connection::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        Connection::create([
            'sender_id' => $acceptedSender->id,
            'receiver_id' => $receiver->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $response = $this->actingAs($receiver, 'api')->getJson('/api/connections/requests');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', Connection::STATUS_PENDING)
            ->assertJsonPath('data.0.message', $sender->first_name.' '.$sender->last_name.' sent you a connection request')
            ->assertJsonPath('data.0.can_accept', true)
            ->assertJsonPath('data.0.can_ignore', true);

        $this->assertDatabaseCount('connections', 2);
    }

    public function test_user_can_search_pending_connection_requests(): void
    {
        $receiver = $this->makeUser('Receiver', 'User');
        $matchingSender = $this->makeUser('Sadia', 'Rahman');
        $nonMatchingSender = $this->makeUser('Tanvir', 'Ahmed');

        Connection::create([
            'sender_id' => $matchingSender->id,
            'receiver_id' => $receiver->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        Connection::create([
            'sender_id' => $nonMatchingSender->id,
            'receiver_id' => $receiver->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        $searchResponse = $this->actingAs($receiver, 'api')
            ->getJson('/api/connections/requests?search=sadia');

        $searchResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Connection requests retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $matchingSender->id)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('limit', 1)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total_page', 1)
            ->assertJsonPath('last_page', 1)
            ->assertJsonPath('stats.total_requests', 2)
            ->assertJsonPath('stats.filtered_requests', 1)
            ->assertJsonPath('filters.search', 'sadia');

        $noMatchResponse = $this->actingAs($receiver, 'api')
            ->getJson('/api/connections/requests?search=not-found');

        $noMatchResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'No pending connection requests found for this search.')
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('total', 0)
            ->assertJsonPath('limit', 0)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total_page', 0)
            ->assertJsonPath('last_page', 0)
            ->assertJsonPath('stats.total_requests', 2)
            ->assertJsonPath('stats.filtered_requests', 0)
            ->assertJsonPath('filters.search', 'not-found');
    }

    public function test_user_can_remove_connection_and_it_removes_mutual_follows(): void
    {
        $firstUser = $this->makeUser();
        $secondUser = $this->makeUser();

        $connectionRequest = Connection::create([
            'sender_id' => $firstUser->id,
            'receiver_id' => $secondUser->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $conversation = Conversation::betweenUsers($firstUser->id, $secondUser->id);

        UserFollow::create([
            'follower_id' => $firstUser->id,
            'following_id' => $secondUser->id,
        ]);

        UserFollow::create([
            'follower_id' => $secondUser->id,
            'following_id' => $firstUser->id,
        ]);

        Event::fake();

        $response = $this->actingAs($firstUser, 'api')->deleteJson('/api/connections/'.$secondUser->id.'/remove');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Connection removed successfully.')
            ->assertJsonPath('data.user.id', $secondUser->id)
            ->assertJsonPath('data.is_connected', false)
            ->assertJsonPath('data.is_following', false);

        Event::assertDispatched(ConnectionRemoved::class, fn (ConnectionRemoved $event): bool => $event->conversation->id === $conversation->id
            && $event->removedUser->id === $secondUser->id
            && $event->removedById === $firstUser->id
            && collect($event->broadcastOn())->contains(
                fn ($channel): bool => $channel->name === 'private-conversation.'.$conversation->id
            ));

        $this->assertDatabaseMissing('connections', [
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

    public function test_user_connections_index_supports_search_sort_and_default_pagination(): void
    {
        $currentUser = $this->makeUser('Current', 'User');

        $connections = [
            ['first_name' => 'Zoe', 'last_name' => 'Zenith'],
            ['first_name' => 'Ben', 'last_name' => 'Beta'],
            ['first_name' => 'Clara', 'last_name' => 'Clark'],
            ['first_name' => 'Dan', 'last_name' => 'Delta'],
            ['first_name' => 'Eva', 'last_name' => 'Echo'],
            ['first_name' => 'Finn', 'last_name' => 'Fox'],
            ['first_name' => 'Gina', 'last_name' => 'Green'],
            ['first_name' => 'Hana', 'last_name' => 'Hart'],
            ['first_name' => 'Ivy', 'last_name' => 'Indigo'],
            ['first_name' => 'Jack', 'last_name' => 'Jones'],
            ['first_name' => 'Amy', 'last_name' => 'Alpha'],
            ['first_name' => 'Mike', 'last_name' => 'Moon'],
        ];

        foreach ($connections as $index => $connectionUserData) {
            $connectionUser = $this->makeUser($connectionUserData['first_name'], $connectionUserData['last_name']);

            Connection::create([
                'sender_id' => $currentUser->id,
                'receiver_id' => $connectionUser->id,
                'status' => Connection::STATUS_ACCEPTED,
                'responded_at' => now()->subDays(11 - $index),
            ]);
        }

        $defaultResponse = $this->actingAs($currentUser, 'api')->getJson('/api/connections');

        $defaultResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Connections retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.user.name', 'Mike Moon')
            ->assertJsonPath('total', 12)
            ->assertJsonPath('limit', 10)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total_page', 2)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('stats.total_connections', 12)
            ->assertJsonPath('stats.filtered_connections', 12)
            ->assertJsonPath('filters.sort', 'recent');

        $searchResponse = $this->actingAs($currentUser, 'api')->getJson('/api/connections?search=amy');

        $searchResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Connections retrieved successfully.')
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.name', 'Amy Alpha')
            ->assertJsonPath('stats.filtered_connections', 1)
            ->assertJsonPath('filters.search', 'amy');

        $oldResponse = $this->actingAs($currentUser, 'api')->getJson('/api/connections?sort=old');

        $oldResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.user.name', 'Zoe Zenith')
            ->assertJsonPath('filters.sort', 'old');

        $azResponse = $this->actingAs($currentUser, 'api')->getJson('/api/connections?sort=az');

        $azResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.user.name', 'Amy Alpha')
            ->assertJsonPath('filters.sort', 'az');
    }

    public function test_connection_list_marks_premium_connections(): void
    {
        $currentUser = $this->makeUser('Current', 'User');

        $premiumConnection = $this->makeUser('Alice', 'Premium');
        $freeConnection = $this->makeUser('Bob', 'Free');

        Connection::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $premiumConnection->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        Connection::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $freeConnection->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        Subscription::create([
            'user_id' => $premiumConnection->id,
            'plan_id' => Plan::create([
                'name' => 'Premium',
                'billing_rate' => 29.99,
                'billing_cycle' => 'monthly',
                'status' => 'active',
                'features' => ['search_profiles'],
            ])->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_premium',
            'status' => 'active',
        ]);

        $response = $this->actingAs($currentUser, 'api')->getJson('/api/connections?sort=az');

        $response->assertOk();

        $alice = collect($response->json('data'))
            ->first(fn (array $item): bool => $item['user']['id'] === $premiumConnection->id);
        $bob = collect($response->json('data'))
            ->first(fn (array $item): bool => $item['user']['id'] === $freeConnection->id);

        $this->assertSame(true, $alice['user']['has_premium']);
        $this->assertSame(false, $bob['user']['has_premium']);
    }

    public function test_user_can_view_connection_suggestions_with_pending_states(): void
    {
        $currentUser = $this->makeUser('Current', 'User');
        $connectedUser = $this->makeUser('Connected', 'Person');
        $pendingSentUser = $this->makeUser('Pending', 'Sent');
        $pendingReceivedUser = $this->makeUser('Pending', 'Received');
        $newSuggestionUser = $this->makeUser('New', 'Suggestion');
        $userWithoutProfile = User::factory()->create([
            'first_name' => 'NoProfile',
            'last_name' => 'Candidate',
            'title' => 'Hidden User',
        ]);

        $unverifiedWithProfile = User::factory()->create([
            'is_verified' => false,
            'first_name' => 'Unverified',
            'last_name' => 'WithProfile',
            'title' => 'Visible User',
        ]);

        UserProfile::create([
            'user_id' => $unverifiedWithProfile->id,
        ]);

        Connection::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $connectedUser->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $pendingSentRequest = Connection::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $pendingSentUser->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        $pendingReceivedRequest = Connection::create([
            'sender_id' => $pendingReceivedUser->id,
            'receiver_id' => $currentUser->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($currentUser, 'api')->getJson('/api/connections/suggestions');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $suggestions = collect($response->json('data'));

        $this->assertFalse($suggestions->contains(fn (array $item): bool => (int) $item['user']['id'] === $connectedUser->id));
        $this->assertFalse($suggestions->contains(fn (array $item): bool => (int) $item['user']['id'] === $userWithoutProfile->id));
        $this->assertTrue($suggestions->contains(fn (array $item): bool => (int) $item['user']['id'] === $unverifiedWithProfile->id));

        $pendingSentItem = $suggestions->first(fn (array $item): bool => (int) $item['user']['id'] === $pendingSentUser->id);
        $this->assertNotNull($pendingSentItem);
        $this->assertSame('pending_sent', $pendingSentItem['state']);
        $this->assertSame('Pending', $pendingSentItem['action_label']);
        $this->assertSame($pendingSentRequest->id, $pendingSentItem['pending_request_id']);
        $this->assertFalse($pendingSentItem['is_connectable']);

        $pendingReceivedItem = $suggestions->first(fn (array $item): bool => (int) $item['user']['id'] === $pendingReceivedUser->id);
        $this->assertNotNull($pendingReceivedItem);
        $this->assertSame('pending_received', $pendingReceivedItem['state']);
        $this->assertSame('Accept', $pendingReceivedItem['action_label']);
        $this->assertSame($pendingReceivedRequest->id, $pendingReceivedItem['pending_request_id']);
        $this->assertFalse($pendingReceivedItem['is_connectable']);

        $newSuggestionItem = $suggestions->first(fn (array $item): bool => (int) $item['user']['id'] === $newSuggestionUser->id);
        $this->assertNotNull($newSuggestionItem);
        $this->assertSame('not_connected', $newSuggestionItem['state']);
        $this->assertSame('Connect', $newSuggestionItem['action_label']);
        $this->assertNull($newSuggestionItem['pending_request_id']);
        $this->assertTrue($newSuggestionItem['is_connectable']);
    }

    public function test_connections_followers_and_following_support_cursor_pagination(): void
    {
        $currentUser = $this->makeUser('Current', 'User');
        $names = [
            ['Alpha', 'One'],
            ['Bravo', 'Two'],
            ['Charlie', 'Three'],
            ['Delta', 'Four'],
            ['Echo', 'Five'],
        ];

        $createdIds = [];
        foreach ($names as $index => [$first, $last]) {
            $other = $this->makeUser($first, $last);
            $createdIds[] = $other->id;

            Connection::create([
                'sender_id' => $currentUser->id,
                'receiver_id' => $other->id,
                'status' => Connection::STATUS_ACCEPTED,
                'responded_at' => now()->subMinutes(10 - $index),
            ]);

            UserFollow::create([
                'follower_id' => $other->id,
                'following_id' => $currentUser->id,
            ]);

            UserFollow::create([
                'follower_id' => $currentUser->id,
                'following_id' => $other->id,
            ]);
        }

        // 1. /api/connections with cursor pagination (sorted A-Z)
        $connP1 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections?limit=2&sort=az')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 5)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.has_more_pages', true);

        $connCursor1 = $connP1->json('pagination.next_cursor');
        $this->assertNotEmpty($connCursor1);

        $connP2 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections?limit=2&sort=az&cursor='.$connCursor1)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.has_more_pages', true);

        $connCursor2 = $connP2->json('pagination.next_cursor');
        $this->assertNotEmpty($connCursor2);

        $connP3 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections?limit=2&sort=az&cursor='.$connCursor2)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('pagination.has_more_pages', false)
            ->assertJsonPath('pagination.next_cursor', null);

        $connUserIds = array_merge(
            array_column(array_column($connP1->json('data'), 'user'), 'id'),
            array_column(array_column($connP2->json('data'), 'user'), 'id'),
            array_column(array_column($connP3->json('data'), 'user'), 'id'),
        );
        $this->assertSame($createdIds, $connUserIds);

        // 2. /api/connections/followers with cursor pagination
        $folP1 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/followers?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 5)
            ->assertJsonPath('pagination.has_more_pages', true);

        $folP2 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/followers?limit=2&cursor='.$folP1->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.has_more_pages', true);

        $folP3 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/followers?limit=2&cursor='.$folP2->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('pagination.has_more_pages', false)
            ->assertJsonPath('pagination.next_cursor', null);

        $followerUserIds = array_merge(
            array_column(array_column($folP1->json('data'), 'user'), 'id'),
            array_column(array_column($folP2->json('data'), 'user'), 'id'),
            array_column(array_column($folP3->json('data'), 'user'), 'id'),
        );
        $this->assertSame(array_reverse($createdIds), $followerUserIds);

        // 3. /api/connections/following with cursor pagination
        $ingP1 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/following?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 5)
            ->assertJsonPath('pagination.has_more_pages', true);

        $ingP2 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/following?limit=2&cursor='.$ingP1->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.has_more_pages', true);

        $ingP3 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/following?limit=2&cursor='.$ingP2->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('pagination.has_more_pages', false)
            ->assertJsonPath('pagination.next_cursor', null);

        $followingUserIds = array_merge(
            array_column(array_column($ingP1->json('data'), 'user'), 'id'),
            array_column(array_column($ingP2->json('data'), 'user'), 'id'),
            array_column(array_column($ingP3->json('data'), 'user'), 'id'),
        );
        $this->assertSame(array_reverse($createdIds), $followingUserIds);
    }

    public function test_requests_sent_requests_and_suggestions_support_cursor_pagination(): void
    {
        $currentUser = $this->makeUser('Current', 'User');

        $senderIds = [];
        $sentIds = [];
        $suggestionIds = [];

        for ($i = 1; $i <= 5; $i++) {
            $sender = $this->makeUser("Sender{$i}", 'Test');
            $senderIds[] = $sender->id;
            Connection::create([
                'sender_id' => $sender->id,
                'receiver_id' => $currentUser->id,
                'status' => Connection::STATUS_PENDING,
                'responded_at' => now()->subMinutes(10 - $i),
            ]);

            $recipient = $this->makeUser("Recipient{$i}", 'Test');
            $sentIds[] = $recipient->id;
            Connection::create([
                'sender_id' => $currentUser->id,
                'receiver_id' => $recipient->id,
                'status' => Connection::STATUS_PENDING,
            ]);

            $candidate = $this->makeUser("Candidate{$i}", 'Suggest');
            $suggestionIds[] = $candidate->id;
        }

        // 1. /api/connections/requests with cursor pagination
        $reqP1 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/requests?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 5)
            ->assertJsonPath('pagination.has_more_pages', true);
        $reqP2 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/requests?limit=2&cursor='.$reqP1->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.has_more_pages', true);
        $reqP3 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/requests?limit=2&cursor='.$reqP2->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('pagination.has_more_pages', false)
            ->assertJsonPath('pagination.next_cursor', null);
        $returnedReqUserIds = array_merge(
            array_column(array_column($reqP1->json('data'), 'user'), 'id'),
            array_column(array_column($reqP2->json('data'), 'user'), 'id'),
            array_column(array_column($reqP3->json('data'), 'user'), 'id'),
        );
        $this->assertSame(array_reverse($senderIds), $returnedReqUserIds);

        // 2. /api/connections/sent-requests with cursor pagination
        $sentP1 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/sent-requests?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 5)
            ->assertJsonPath('pagination.has_more_pages', true);
        $sentP2 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/sent-requests?limit=2&cursor='.$sentP1->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.has_more_pages', true);
        $sentP3 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/sent-requests?limit=2&cursor='.$sentP2->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('pagination.has_more_pages', false)
            ->assertJsonPath('pagination.next_cursor', null);
        $returnedSentUserIds = array_merge(
            array_column(array_column($sentP1->json('data'), 'user'), 'id'),
            array_column(array_column($sentP2->json('data'), 'user'), 'id'),
            array_column(array_column($sentP3->json('data'), 'user'), 'id'),
        );
        $this->assertSame(array_reverse($sentIds), $returnedSentUserIds);

        // 3. /api/connections/suggestions with cursor pagination
        $sugP1 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/suggestions?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 5)
            ->assertJsonPath('pagination.has_more_pages', true);
        $sugP2 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/suggestions?per_page=2&cursor='.$sugP1->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.has_more_pages', true);
        $sugP3 = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/suggestions?per_page=2&cursor='.$sugP2->json('pagination.next_cursor'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('pagination.has_more_pages', false)
            ->assertJsonPath('pagination.next_cursor', null);
        $returnedSugUserIds = array_merge(
            array_column(array_column($sugP1->json('data'), 'user'), 'id'),
            array_column(array_column($sugP2->json('data'), 'user'), 'id'),
            array_column(array_column($sugP3->json('data'), 'user'), 'id'),
        );
        $this->assertSame(array_reverse($suggestionIds), $returnedSugUserIds);
    }

    public function test_sent_requests_include_mutual_connections_and_sent_since(): void
    {
        $currentUser = $this->makeUser('Current', 'User');
        $mutualFriend = $this->makeUser('Mutual', 'Friend');
        $recipient = $this->makeUser('Pending', 'Recipient');

        Connection::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $mutualFriend->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now()->subDays(5),
        ]);

        Connection::create([
            'sender_id' => $mutualFriend->id,
            'receiver_id' => $recipient->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now()->subDays(3),
        ]);

        $sentConnection = Connection::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $recipient->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/sent-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $recipient->id)
            ->assertJsonPath('data.0.sent_since', $sentConnection->created_at->format('d M, Y'))
            ->assertJsonPath('data.0.mutual_connections_count', 1)
            ->assertJsonPath('data.0.mutual_connections.0.id', $mutualFriend->id);

        $this->assertNotEmpty($response->json('data.0.requested_at'));
    }

    public function test_received_requests_include_mutual_connections_and_profession_title_fallback(): void
    {
        $currentUser = $this->makeUser('Current', 'User');
        $mutualFriend = $this->makeUser('Mutual', 'Friend');

        $sender = User::factory()->create([
            'is_verified' => true,
            'first_name' => 'Marcus',
            'last_name' => 'Thorne',
            'title' => null,
        ]);
        UserProfile::create([
            'user_id' => $sender->id,
            'profession' => ['Behavioral Health Specialist', 'Academic Lecturer'],
        ]);

        Connection::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $mutualFriend->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now()->subDays(5),
        ]);

        Connection::create([
            'sender_id' => $mutualFriend->id,
            'receiver_id' => $sender->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now()->subDays(3),
        ]);

        $receivedConnection = Connection::create([
            'sender_id' => $sender->id,
            'receiver_id' => $currentUser->id,
            'status' => Connection::STATUS_PENDING,
        ]);

        $this->actingAs($currentUser, 'api')
            ->getJson('/api/connections/requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $sender->id)
            ->assertJsonPath('data.0.user.title', 'Behavioral Health Specialist | Academic Lecturer')
            ->assertJsonPath('data.0.sent_since', $receivedConnection->created_at->format('d M, Y'))
            ->assertJsonPath('data.0.mutual_connections_count', 1)
            ->assertJsonPath('data.0.mutual_connections.0.id', $mutualFriend->id);
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
