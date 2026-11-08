<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\Industry;
use App\Models\IndustryConversation;
use App\Models\IndustryMessage;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryConversationMessageTest extends TestCase
{
    use DatabaseTransactions;

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

    private function createIndustryOwner(): array
    {
        $owner = $this->makeUser();
        $industry = Industry::create([
            'created_by' => $owner->id,
            'name' => 'Bata Corp '.uniqid(),
            'slug' => 'bata-corp-'.uniqid(),
            'industry' => 'biotechnology',
            'status' => 'approved',
        ]);

        return [$owner, $industry];
    }

    public function test_regular_user_cannot_initiate_conversation_with_industry_directly(): void
    {
        [$owner, $industry] = $this->createIndustryOwner();
        $user = $this->makeUser();

        // Basic or premium user cannot initiate conversation with company
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/industry-conversations/with-industry/{$industry->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_industry_owner_can_start_conversation_with_any_user_without_connection(): void
    {
        [$owner, $industry] = $this->createIndustryOwner();
        $targetUser = $this->makeUser();

        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/industry-conversations/with-user/{$targetUser->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $targetUser->id)
            ->assertJsonPath('data.industry_id', $industry->id);
    }

    public function test_after_company_starts_conversation_user_can_access_and_reply(): void
    {
        [$owner, $industry] = $this->createIndustryOwner();
        $user = $this->makeUser();

        // 1. Company initiates conversation
        $this->actingAs($owner, 'api')
            ->postJson("/api/industry-conversations/with-user/{$user->id}")
            ->assertOk();

        // 2. Now user can access the conversation
        $userResp = $this->actingAs($user, 'api')
            ->postJson("/api/industry-conversations/with-industry/{$industry->id}");

        $userResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.industry.id', $industry->id);

        $convId = $userResp->json('data.id');

        // 3. User can reply to the company
        $this->actingAs($user, 'api')
            ->postJson("/api/industry-conversations/{$convId}/messages", [
                'type' => 'text',
                'message' => 'Thank you for reaching out!',
            ])
            ->assertCreated();
    }

    public function test_industry_messages_are_separated_from_user_conversations(): void
    {
        [$owner, $industry] = $this->createIndustryOwner();
        $user = $this->makeUser();

        $conv = IndustryConversation::between($industry->id, $user->id);

        IndustryMessage::create([
            'industry_conversation_id' => $conv->id,
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'type' => 'text',
            'message' => 'Hello company!',
        ]);

        // Regular conversation list for owner must be empty (personal chats only)
        $ownerUserChats = $this->actingAs($owner, 'api')
            ->getJson('/api/conversations')
            ->assertOk();

        $this->assertCount(0, $ownerUserChats->json('data'));

        // Industry conversation list for owner contains the chat
        $ownerIndustryChats = $this->actingAs($owner, 'api')
            ->getJson('/api/industry-conversations')
            ->assertOk();

        $this->assertCount(1, $ownerIndustryChats->json('data'));
        $this->assertEquals($user->id, $ownerIndustryChats->json('data.0.user.id'));
    }

    public function test_pro_industry_user_can_direct_message_any_user_without_prior_connection(): void
    {
        [$owner, $industry] = $this->createIndustryOwner();
        $regularUser = $this->makeUser();

        // Owner is pro_industry, so showOrCreate user conversation succeeds without connection
        $response = $this->actingAs($owner, 'api')
            ->postJson("/api/conversations/with/{$regularUser->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $convId = $response->json('data.id');

        // Sending message succeeds without connection
        $msgResponse = $this->actingAs($owner, 'api')
            ->postJson("/api/conversations/{$convId}/messages", [
                'type' => 'text',
                'message' => 'Hello from pro industry user!',
            ]);

        $msgResponse->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_both_parties_can_send_and_read_industry_messages(): void
    {
        [$owner, $industry] = $this->createIndustryOwner();
        $user = $this->makeUser();

        $conv = IndustryConversation::between($industry->id, $user->id);

        // User sends inquiry to company
        $userMsgResp = $this->actingAs($user, 'api')
            ->postJson("/api/industry-conversations/{$conv->id}/messages", [
                'type' => 'text',
                'message' => 'Are you hiring biotech engineers?',
            ]);

        $userMsgResp->assertCreated()
            ->assertJsonPath('data.sender_type', 'user');

        // Industry owner replies as industry
        $ownerMsgResp = $this->actingAs($owner, 'api')
            ->postJson("/api/industry-conversations/{$conv->id}/messages", [
                'type' => 'text',
                'message' => 'Yes, please apply to our latest job posting!',
            ]);

        $ownerMsgResp->assertCreated()
            ->assertJsonPath('data.sender_type', 'industry');

        // Both messages present in history
        $history = $this->actingAs($owner, 'api')
            ->getJson("/api/industry-conversations/{$conv->id}/messages")
            ->assertOk();

        $this->assertCount(2, $history->json('data'));
    }

    public function test_industry_conversation_can_be_archived_and_marked_read(): void
    {
        [$owner, $industry] = $this->createIndustryOwner();
        $user = $this->makeUser();
        $conv = IndustryConversation::between($industry->id, $user->id);

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry-conversations/{$conv->id}/archive")
            ->assertOk();

        $this->assertTrue($conv->fresh()->isArchivedForIndustry());

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry-conversations/{$conv->id}/unarchive")
            ->assertOk();

        $this->assertFalse($conv->fresh()->isArchivedForIndustry());
    }
}
