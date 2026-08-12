<?php

namespace Tests\Feature\Api;

use App\Events\MessageSent;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\FcmToken;
use App\Models\Message;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserProfile;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ConversationMessageFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_text_message(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        FcmToken::create(['user_id' => $connectedUser->id, 'fcm_token' => 'dummy-token']);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $response = $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Hello, how are you?',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'text')
            ->assertJsonPath('data.message', 'Hello, how are you?')
            ->assertJsonPath('data.sender_id', $user->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'text',
            'message' => 'Hello, how are you?',
        ]);

        $this->assertEquals($response->json('data.id'), $conversation->fresh()->last_message_id);

        Notification::assertSentTo(
            [$connectedUser],
            NewMessageNotification::class
        );
    }

    public function test_user_can_send_file_message(): void
    {
        Notification::fake();

        Storage::fake('public');

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $file = UploadedFile::fake()->image('photo.jpg', 200, 200);

        $response = $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'file',
            'file' => $file,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'file');

        $this->assertNotNull($response->json('data.file_url'));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'file',
            'file_name' => 'photo.jpg',
        ]);
    }

    public function test_messages_list_includes_premium_status_for_other_user(): void
    {
        $user = $this->makeUser();
        $premiumUser = $this->makeUser();
        $this->connectUsers($user, $premiumUser);

        $conversation = Conversation::betweenUsers($user->id, $premiumUser->id);

        Subscription::create([
            'user_id' => $premiumUser->id,
            'plan_id' => Plan::create([
                'name' => 'Premium', 'billing_rate' => 29.99, 'billing_cycle' => 'monthly',
                'status' => 'active', 'features' => ['search_profiles'],
            ])->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
            'current_period_end' => now()->addDays(20),
        ]);

        $response = $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('other_user.has_premium', true)
            ->assertJsonPath('other_user.id', $premiumUser->id);
    }

    public function test_messages_include_file_category_and_extension(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'file',
            'file_path' => 'conversations/1/photo.jpg',
            'file_name' => 'photo.jpg',
            'file_size' => 1000,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'file',
            'file_path' => 'conversations/1/doc.pdf',
            'file_name' => 'doc.pdf',
            'file_size' => 2000,
        ]);

        $response = $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages");

        $response
            ->assertOk()
            ->assertJsonPath('data.0.file_category', 'image')
            ->assertJsonPath('data.0.file_extension', 'jpg')
            ->assertJsonPath('data.1.file_category', 'pdf')
            ->assertJsonPath('data.1.file_extension', 'pdf');
    }

    public function test_user_can_send_voice_message(): void
    {
        Notification::fake();

        Storage::fake('public');

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $file = UploadedFile::fake()->create('voice.mp3', 500, 'audio/mpeg');

        $response = $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'voice',
            'file' => $file,
            'duration' => 30,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'voice')
            ->assertJsonPath('data.duration', 30);
    }

    public function test_message_sent_event_is_dispatched(): void
    {
        Event::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Test event',
        ]);

        Event::assertDispatched(MessageSent::class, function ($event) use ($conversation, $user) {
            return $event->message->conversation_id === $conversation->id
                && $event->message->sender_id === $user->id;
        });
    }

    public function test_unread_count_tracks_correctly(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        // User sends message — connectedUser should have 1 unread
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'First message',
        ]);

        $userConversations = $this->actingAs($connectedUser, 'api')->getJson('/api/conversations');
        $userConversations->assertJsonPath('data.0.unread_count', 1);

        // connectedUser sends message — user should have 1 unread too
        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Reply!',
        ]);

        $userConversations = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $userConversations->assertJsonPath('data.0.unread_count', 1);

        // connectedUser sends another — connectedUser should have 1 unread (only user's message)
        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Another reply!',
        ]);

        $userConversations = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $userConversations->assertJsonPath('data.0.unread_count', 2);
    }

    public function test_mark_as_read_resets_unread_count(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        // connectedUser sends messages
        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Message 1',
        ]);

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Message 2',
        ]);

        // User has 2 unread
        $before = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $before->assertJsonPath('data.0.unread_count', 2);

        // Mark as read
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/mark-read")
            ->assertOk()
            ->assertJsonPath('success', true);

        // Now 0 unread
        $after = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $after->assertJsonPath('data.0.unread_count', 0);
    }

    public function test_user_can_get_paginated_messages(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        // Create multiple messages
        for ($i = 0; $i < 5; $i++) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $i % 2 === 0 ? $user->id : $connectedUser->id,
                'type' => 'text',
                'message' => "Message {$i}",
            ]);
        }
        $conversation->update(['last_message_id' => $conversation->messages()->latest()->first()->id]);

        $response = $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('other_user.id', $connectedUser->id)
            ->assertJsonPath('data.0.message', 'Message 0')
            ->assertJsonPath('data.4.message', 'Message 4');
    }

    public function test_user_can_delete_own_message(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'text',
            'message' => 'Delete me',
        ]);

        $response = $this->actingAs($user, 'api')->deleteJson("/api/messages/{$message->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    public function test_user_cannot_delete_others_message(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'text',
            'message' => 'Not yours',
        ]);

        $response = $this->actingAs($connectedUser, 'api')->deleteJson("/api/messages/{$message->id}");

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You can only delete your own messages.');

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'deleted_at' => null]);
    }

    public function test_unauthorized_user_cannot_access_conversation(): void
    {
        $user = $this->makeUser();
        $user2 = $this->makeUser();
        $stranger = $this->makeUser();
        $this->connectUsers($user, $user2);

        $conversation = Conversation::betweenUsers($user->id, $user2->id);

        $response = $this->actingAs($stranger, 'api')->getJson("/api/conversations/{$conversation->id}/messages");

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_conversation_list_shows_other_user_and_last_message(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser('Alice', 'Johnson');
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hey there!',
        ]);
        $conversation->update(['last_message_id' => $message->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/conversations');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.name', 'Alice Johnson')
            ->assertJsonPath('data.0.last_message.message', 'Hey there!')
            ->assertJsonPath('data.0.last_message.sender_id', $connectedUser->id)
            ->assertJsonPath('data.0.unread_count', 1);
    }

    public function test_conversation_list_empty_when_no_conversations(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'api')->getJson('/api/conversations');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_new_message_notification_is_sent_to_receiver(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        FcmToken::create(['user_id' => $connectedUser->id, 'fcm_token' => 'dummy-token']);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Notification test',
        ]);

        Notification::assertSentTo(
            $connectedUser,
            NewMessageNotification::class,
            function (NewMessageNotification $notification) use ($user, $conversation) {
                return $notification->message->conversation_id === $conversation->id
                    && $notification->message->sender_id === $user->id;
            }
        );
    }

    public function test_mark_as_unread_resets_unread_to_total(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Message 1',
        ]);

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Message 2',
        ]);

        // Mark as read first
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/mark-read");
        $afterRead = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $afterRead->assertJsonPath('data.0.unread_count', 0);

        // Mark as unread — unread should now include both messages
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/mark-unread")
            ->assertOk()
            ->assertJsonPath('success', true);

        $afterUnread = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $afterUnread->assertJsonPath('data.0.unread_count', 2);
    }

    public function test_user_can_archive_and_unarchive_conversation(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Hello',
        ]);

        // Not archived in main list
        $list = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $list->assertJsonPath('data.0.is_archived', false);

        // Archive
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/archive")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_archived', true);

        // Should be gone from main list
        $mainList = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $mainList->assertJsonCount(0, 'data');

        // Should appear in archived list
        $archivedList = $this->actingAs($user, 'api')->getJson('/api/conversations?status=archived');
        $archivedList->assertJsonCount(1, 'data');
        $archivedList->assertJsonPath('data.0.is_archived', true);

        // ConnectedUser's list should NOT be affected (still shows)
        $otherList = $this->actingAs($connectedUser, 'api')->getJson('/api/conversations');
        $otherList->assertJsonCount(1, 'data');
        $otherList->assertJsonPath('data.0.is_archived', false);

        // Unarchive
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/unarchive")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_archived', false);

        // Back in main list
        $finalList = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $finalList->assertJsonCount(1, 'data');
        $finalList->assertJsonPath('data.0.is_archived', false);
    }

    public function test_conversation_list_includes_premium_status_for_other_user(): void
    {
        $user = $this->makeUser();
        $premiumUser = $this->makeUser();
        $freeUser = $this->makeUser();
        $this->connectUsers($user, $premiumUser);
        $this->connectUsers($user, $freeUser);

        Conversation::betweenUsers($user->id, $premiumUser->id);
        Conversation::betweenUsers($user->id, $freeUser->id);

        Subscription::create([
            'user_id' => $premiumUser->id,
            'plan_id' => Plan::create([
                'name' => 'Premium', 'billing_rate' => 29.99, 'billing_cycle' => 'monthly',
                'status' => 'active', 'features' => ['search_profiles'],
            ])->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
            'current_period_end' => now()->addDays(20),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/conversations');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.user.has_premium', true)
            ->assertJsonPath('data.1.user.has_premium', false);
    }

    public function test_user_can_react_to_message(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);
        $message = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hello!',
        ]);

        $response = $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", [
            'reaction' => '👍',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('my_reaction', '👍')
            ->assertJsonPath('reactions.0.reaction', '👍')
            ->assertJsonPath('reactions.0.count', 1)
            ->assertJsonPath('reactions.0.users.0.id', $user->id)
            ->assertJsonPath('reactions.0.users.0.name', trim(($user->first_name).' '.($user->last_name)));

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => '👍',
        ]);
    }

    public function test_reacting_with_same_reaction_again_removes_it(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);
        $message = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hello!',
        ]);

        $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", [
            'reaction' => '👍',
        ]);

        $response = $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", [
            'reaction' => '👍',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('my_reaction', null)
            ->assertJsonPath('reactions', []);

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_change_reaction(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);
        $message = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hello!',
        ]);

        $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", [
            'reaction' => '👍',
        ]);

        $response = $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", [
            'reaction' => '❤️',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('my_reaction', '❤️')
            ->assertJsonPath('reactions.0.reaction', '❤️')
            ->assertJsonPath('reactions.0.count', 1);

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => '❤️',
        ]);
    }

    public function test_multiple_users_can_react_to_same_message(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);
        $message = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hello!',
        ]);

        $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", ['reaction' => '👍']);
        $this->actingAs($connectedUser, 'api')->postJson("/api/messages/{$message->id}/react", ['reaction' => '👍']);

        $response = $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages");

        $response
            ->assertOk()
            ->assertJsonPath('data.0.my_reaction', '👍')
            ->assertJsonCount(1, 'data.0.reactions')
            ->assertJsonPath('data.0.reactions.0.reaction', '👍')
            ->assertJsonPath('data.0.reactions.0.count', 2);
    }

    public function test_user_can_unreact_to_message(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);
        $message = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hello!',
        ]);

        $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", ['reaction' => '👍']);

        $response = $this->actingAs($user, 'api')->deleteJson("/api/messages/{$message->id}/react");

        $response
            ->assertOk()
            ->assertJsonPath('my_reaction', null)
            ->assertJsonPath('reactions', []);

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_non_participant_cannot_react_to_message(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $outsider = $this->makeUser();

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);
        $message = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hello!',
        ]);

        $this->actingAs($outsider, 'api')->postJson("/api/messages/{$message->id}/react", ['reaction' => '👍'])
            ->assertForbidden();

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $outsider->id,
        ]);
    }

    public function test_reaction_requires_reaction_field(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);
        $message = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hello!',
        ]);

        $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reaction');
    }

    public function test_reaction_must_be_a_single_emoji(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);
        $message = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hello!',
        ]);

        $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", [
            'reaction' => '😀😀😀',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('reaction');

        $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", [
            'reaction' => '😀❤️',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('reaction');

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", [
            'reaction' => '👍🏽',
        ])->assertOk()
            ->assertJsonPath('my_reaction', '👍🏽');
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
        UserProfile::create(['user_id' => $user->id]);

        return $user;
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
}
