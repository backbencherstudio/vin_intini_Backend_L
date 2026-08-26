<?php

namespace Tests\Feature\Api;

use App\Events\ConversationUpdated;
use App\Events\MessageReactionChanged;
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

    public function test_user_can_reply_to_a_message(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $original = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Original message',
        ]);

        $response = $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'My reply',
            'reply_to_id' => $original->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.reply_to.id', $original->id)
            ->assertJsonPath('data.reply_to.message', 'Original message')
            ->assertJsonPath('data.reply_to.sender_name', trim(($connectedUser->first_name).' '.($connectedUser->last_name)));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => 'My reply',
            'reply_to_id' => $original->id,
        ]);
    }

    public function test_reply_must_reference_message_in_same_conversation(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $otherConversation = Conversation::betweenUsers($this->makeUser()->id, $this->makeUser()->id);
        $foreignMessage = $otherConversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'From another conversation',
        ]);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'My reply',
            'reply_to_id' => $foreignMessage->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('reply_to_id');
    }

    public function test_messages_list_includes_reply_preview(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $original = $conversation->messages()->create([
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Original message',
        ]);

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'type' => 'text',
            'message' => 'My reply',
            'reply_to_id' => $original->id,
        ]);

        $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.1.reply_to.id', $original->id)
            ->assertJsonPath('data.1.reply_to.message', 'Original message');
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

    public function test_audio_file_duration_is_auto_extracted(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $sampleRate = 8000;
        $seconds = 2;
        $dataSize = $sampleRate * $seconds * 2;
        $wav = pack('CCCC', 0x52, 0x49, 0x46, 0x46) // RIFF
            .pack('V', 36 + $dataSize)
            .pack('CCCC', 0x57, 0x41, 0x56, 0x45) // WAVE
            .pack('CCCC', 0x66, 0x6D, 0x74, 0x20) // fmt
            .pack('V', 16)
            .pack('v', 1)
            .pack('v', 1)
            .pack('V', $sampleRate)
            .pack('V', $sampleRate * 2)
            .pack('v', 2)
            .pack('v', 16)
            .pack('CCCC', 0x64, 0x61, 0x74, 0x61) // data
            .pack('V', $dataSize)
            .str_repeat(pack('s', 0), $sampleRate * $seconds);

        $file = UploadedFile::fake()->createWithContent('voice.wav', $wav);

        $response = $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'voice',
            'file' => $file,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.duration', 2);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'file_name' => 'voice.wav',
            'duration' => 2,
        ]);
    }

    public function test_client_provided_duration_is_kept(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $file = UploadedFile::fake()->createWithContent('voice.wav', 'not-real-audio');

        $response = $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'voice',
            'file' => $file,
            'duration' => 7,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.duration', 7);
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

    public function test_fetching_messages_marks_conversation_as_read(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Unread message',
        ]);

        $before = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $before->assertJsonPath('data.0.unread_count', 1);

        $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk();

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

    public function test_total_unread_count_returns_global_summary(): void
    {
        $user = $this->makeUser();

        $unreadUsers = ['a', 'b', 'c', 'd'];
        $messagesPerUser = ['a' => 1, 'b' => 4, 'c' => 5, 'd' => 2];

        foreach ($unreadUsers as $name) {
            $other = $this->makeUser();
            $this->connectUsers($user, $other);

            $conversation = Conversation::betweenUsers($user->id, $other->id);

            for ($i = 0; $i < $messagesPerUser[$name]; $i++) {
                $this->actingAs($other, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
                    'type' => 'text',
                    'message' => "Hello from {$name} {$i}",
                ]);
            }
        }

        $readUser = $this->makeUser();
        $this->connectUsers($user, $readUser);
        $readConversation = Conversation::betweenUsers($user->id, $readUser->id);
        $this->actingAs($readUser, 'api')->postJson("/api/conversations/{$readConversation->id}/messages", [
            'type' => 'text',
            'message' => 'Already read',
        ]);
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$readConversation->id}/mark-read");

        $this->actingAs($user, 'api')->getJson('/api/conversations/unread-count')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_conversation_count', 4)
            ->assertJsonPath('data.total_unread_messages', 12);
    }

    public function test_total_unread_count_is_zero_when_everything_is_read(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'api')->getJson('/api/conversations/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_conversation_count', 0)
            ->assertJsonPath('data.total_unread_messages', 0);
    }

    public function test_conversation_updated_event_broadcasts_unread_count_to_receiver(): void
    {
        Event::fake([MessageSent::class, ConversationUpdated::class]);
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Hello!',
        ])->assertCreated();

        Event::assertDispatched(ConversationUpdated::class, function (ConversationUpdated $event) use ($conversation, $connectedUser, $user) {
            return $event->conversation->is($conversation)
                && $event->receiver->id === $connectedUser->id
                && $event->unreadCount === 1
                && $event->unreadConversationCount === 1
                && $event->totalUnreadMessages === 1
                && $event->message->sender_id === $user->id
                && $event->broadcastOn()[0]->name === 'private-App.Models.User.'.$connectedUser->id;
        });
    }

    public function test_no_event_or_notification_when_receiver_archived_conversation(): void
    {
        Event::fake([MessageSent::class, ConversationUpdated::class]);
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);
        $conversation->archiveFor($connectedUser->id);

        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Hello!',
        ])->assertCreated();

        Event::assertNotDispatched(ConversationUpdated::class, function (ConversationUpdated $event) use ($connectedUser) {
            return $event->receiver->id === $connectedUser->id;
        });

        Notification::assertNotSentTo($connectedUser, NewMessageNotification::class);
    }

    public function test_conversation_updated_event_broadcasts_zeroed_counts_when_opening_chat(): void
    {
        Event::fake([MessageSent::class, ConversationUpdated::class]);
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Hello!',
        ])->assertCreated();

        $this->actingAs($connectedUser, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk();

        Event::assertDispatched(ConversationUpdated::class, function (ConversationUpdated $event) use ($conversation, $connectedUser) {
            return $event->conversation->is($conversation)
                && $event->receiver->id === $connectedUser->id
                && $event->unreadCount === 0
                && $event->unreadConversationCount === 0
                && $event->totalUnreadMessages === 0
                && $event->message === null
                && $event->broadcastOn()[0]->name === 'private-App.Models.User.'.$connectedUser->id;
        });
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
            ->assertJsonPath('data.0.last_message.type', 'text')
            ->assertJsonPath('data.0.last_message.sender_id', $connectedUser->id)
            ->assertJsonPath('data.0.unread_count', 1);
    }

    public function test_conversation_list_last_message_type_reflects_file_kind(): void
    {
        $user = $this->makeUser();
        $connectedUser = $this->makeUser('Alice', 'Johnson');
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'file',
            'file_path' => 'conversations/1/photo.jpg',
            'file_name' => 'photo.jpg',
            'file_size' => 1000,
        ]);
        $conversation->update(['last_message_id' => $message->id]);

        $this->actingAs($user, 'api')->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.last_message.type', 'image');
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
        Event::fake([MessageReactionChanged::class]);

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

        Event::assertDispatched(MessageReactionChanged::class, fn (MessageReactionChanged $event) => $event->message->is($message)
            && $event->reaction === '👍'
            && $event->userId === $user->id);

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $user->id,
            'reaction' => '👍',
        ]);
    }

    public function test_reacting_with_same_reaction_again_removes_it(): void
    {
        Event::fake([MessageReactionChanged::class]);

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
        Event::fake([MessageReactionChanged::class]);

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

        Event::assertDispatched(
            MessageReactionChanged::class,
            fn (MessageReactionChanged $event) => $event->message->is($message)
                && $event->reaction === null
                && $event->userId === $user->id
        );
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
            'user_id' => $user->id,
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

    public function test_user_can_delete_conversation_for_self_only(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Keep me for the other user',
        ]);
        $conversation->update(['last_message_id' => $message->id]);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        // Hidden for the deleting user
        $deleterList = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $deleterList->assertOk()->assertJsonCount(0, 'data');

        // Still visible for the other participant
        $otherList = $this->actingAs($connectedUser, 'api')->getJson('/api/conversations');
        $otherList
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_message.message', 'Keep me for the other user');

        // Nothing is actually destroyed
        $this->assertDatabaseHas('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'deleted_at' => null]);
        $this->assertFalse($conversation->fresh()->isDeletedFor($connectedUser->id));
        $this->assertTrue($conversation->fresh()->isDeletedFor($user->id));
    }

    public function test_both_users_can_delete_conversation_independently(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();
        $this->actingAs($connectedUser, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $this->actingAs($user, 'api')->getJson('/api/conversations')->assertJsonCount(0, 'data');
        $this->actingAs($connectedUser, 'api')->getJson('/api/conversations')->assertJsonCount(0, 'data');

        $this->assertDatabaseHas('conversations', ['id' => $conversation->id]);
    }

    public function test_deleted_conversation_is_inaccessible_for_deleter_but_visible_for_other(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hello',
        ]);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        // Deleter gets 404 everywhere
        $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertNotFound()
            ->assertJsonPath('success', false);
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Should fail',
        ])->assertNotFound();
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/mark-read")
            ->assertNotFound();
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/archive")
            ->assertNotFound();

        // Reacting to a message of a hidden conversation is blocked too
        $this->actingAs($user, 'api')->postJson("/api/messages/{$message->id}/react", ['reaction' => '👍'])
            ->assertNotFound();

        // The other participant keeps full access
        $this->actingAs($connectedUser, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.message', 'Hello');
    }

    public function test_new_message_from_other_party_restores_deleted_conversation(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Are you there?',
        ])->assertCreated();

        $list = $this->actingAs($user, 'api')->getJson('/api/conversations');
        $list
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_message.message', 'Are you there?')
            ->assertJsonPath('data.0.unread_count', 1);

        $this->assertFalse($conversation->fresh()->isDeletedFor($user->id));
    }

    public function test_show_or_create_restores_deleted_conversation_for_caller(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();
        $this->actingAs($connectedUser, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $response = $this->actingAs($user, 'api')->postJson("/api/conversations/with/{$connectedUser->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $conversation->id);

        // Restored only for the caller
        $this->assertFalse($conversation->fresh()->isDeletedFor($user->id));
        $this->assertTrue($conversation->fresh()->isDeletedFor($connectedUser->id));

        $this->actingAs($user, 'api')->getJson('/api/conversations')->assertJsonCount(1, 'data');
        $this->actingAs($connectedUser, 'api')->getJson('/api/conversations')->assertJsonCount(0, 'data');
    }

    public function test_unread_summary_excludes_deleted_conversations(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Unread message',
        ])->assertCreated();

        $before = $this->actingAs($user, 'api')->getJson('/api/conversations/unread-count');
        $before
            ->assertOk()
            ->assertJsonPath('data.unread_conversation_count', 1)
            ->assertJsonPath('data.total_unread_messages', 1);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $after = $this->actingAs($user, 'api')->getJson('/api/conversations/unread-count');
        $after
            ->assertOk()
            ->assertJsonPath('data.unread_conversation_count', 0)
            ->assertJsonPath('data.total_unread_messages', 0);

        // Other user's summary is unaffected
        $other = $this->actingAs($connectedUser, 'api')->getJson('/api/conversations/unread-count');
        $other->assertJsonPath('data.total_unread_messages', 0);
    }

    public function test_history_stays_hidden_when_conversation_restored_by_new_message(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $oldMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Old message',
        ]);
        $conversation->update(['last_message_id' => $oldMessage->id]);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Brand new message',
        ])->assertCreated();

        // The deleter only sees the new message, not the cleared history
        $this->actingAs($user, 'api')->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_message.message', 'Brand new message')
            ->assertJsonPath('data.0.unread_count', 1);

        $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Brand new message');

        // The other participant still sees the full history
        $this->actingAs($connectedUser, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.message', 'Old message')
            ->assertJsonPath('data.1.message', 'Brand new message');
    }

    public function test_show_or_create_does_not_reveal_cleared_history(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $oldMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Old message',
        ]);
        $conversation->update(['last_message_id' => $oldMessage->id]);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $this->actingAs($user, 'api')->postJson("/api/conversations/with/{$connectedUser->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $conversation->id)
            ->assertJsonPath('data.last_message', null)
            ->assertJsonPath('data.unread_count', 0);

        $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // The other participant keeps the full history
        $this->actingAs($connectedUser, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Old message');
    }

    public function test_unread_counts_ignore_messages_sent_before_the_delete(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        foreach (['Old message 1', 'Old message 2'] as $text) {
            $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
                'type' => 'text',
                'message' => $text,
            ])->assertCreated();
        }

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'New message after delete',
        ])->assertCreated();

        $this->actingAs($user, 'api')->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.unread_count', 1);

        $this->actingAs($user, 'api')->getJson('/api/conversations/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_conversation_count', 1)
            ->assertJsonPath('data.total_unread_messages', 1);
    }

    public function test_deleting_again_after_restore_hides_everything_from_that_point(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Old message',
        ]);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'First new message',
        ])->assertCreated();

        $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Deleting again moves the clear point forward
        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Second new message',
        ])->assertCreated();

        $this->actingAs($user, 'api')->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Second new message');
    }

    public function test_replying_to_cleared_message_is_rejected_for_deleter(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $clearedMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Hidden message',
        ]);

        $this->actingAs($user, 'api')->deleteJson("/api/conversations/{$conversation->id}")->assertOk();
        $this->actingAs($user, 'api')->postJson("/api/conversations/with/{$connectedUser->id}")->assertOk();

        // The deleter can no longer reply to the hidden message
        $this->actingAs($user, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Reply to hidden',
            'reply_to_id' => $clearedMessage->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('reply_to_id');

        // The other participant still can — the cutoff is per user
        $this->actingAs($connectedUser, 'api')->postJson("/api/conversations/{$conversation->id}/messages", [
            'type' => 'text',
            'message' => 'Still works for me',
            'reply_to_id' => $clearedMessage->id,
        ])->assertCreated()
            ->assertJsonPath('data.reply_to.id', $clearedMessage->id);
    }

    public function test_deleting_last_message_falls_back_to_previous_in_conversation_list(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'text',
            'message' => 'Older message',
        ]);

        $lastMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Newest message',
        ]);
        $conversation->update(['last_message_id' => $lastMessage->id]);

        Event::fake([ConversationUpdated::class]);

        $this->actingAs($connectedUser, 'api')->deleteJson("/api/messages/{$lastMessage->id}")->assertOk();

        $this->actingAs($user, 'api')->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_message.message', 'Older message');

        $this->assertEquals(
            $conversation->messages()->max('id'),
            $conversation->fresh()->last_message_id
        );

        Event::assertDispatched(ConversationUpdated::class);
    }

    public function test_deleting_only_message_clears_last_message_preview(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $connectedUser = $this->makeUser();
        $this->connectUsers($user, $connectedUser);

        $conversation = Conversation::betweenUsers($user->id, $connectedUser->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $connectedUser->id,
            'type' => 'text',
            'message' => 'Only message',
        ]);
        $conversation->update(['last_message_id' => $message->id]);

        $this->actingAs($connectedUser, 'api')->deleteJson("/api/messages/{$message->id}")->assertOk();

        $list = $this->actingAs($user, 'api')->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $list->assertJsonPath('data.0.last_message', null);

        $this->assertNull($conversation->fresh()->last_message_id);
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
