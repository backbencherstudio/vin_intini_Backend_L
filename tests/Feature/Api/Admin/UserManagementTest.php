<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\PlanType;
use App\Models\Company;
use App\Models\Connection;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Industry;
use App\Models\Institution;
use App\Models\LoginActivity;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Skill;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $adminRole;

    private Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->userRole = Role::create(['name' => 'user', 'guard_name' => 'api']);

        $this->admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'Master',
            'email' => 'admin@gmail.com',
            'is_verified' => true,
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $regularUser = User::factory()->create(['email' => 'user@test.com', 'is_verified' => true]);
        $regularUser->assignRole($this->userRole);

        $response = $this->actingAs($regularUser, 'api')
            ->getJson('/api/admin/user-manage/users?type=basic');

        $response->assertForbidden();
    }

    public function test_admin_can_list_basic_users(): void
    {
        $basicUser = User::factory()->create([
            'first_name' => 'Basic',
            'last_name' => 'Person',
            'email' => 'basic@test.com',
            'is_verified' => true,
        ]);
        $basicUser->assignRole($this->userRole);

        // Premium user should not appear in basic users
        $premiumPlan = Plan::create([
            'name' => 'Pro User',
            'billing_rate' => 19.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'plan_type' => PlanType::PREMIUM,
            'features' => ['build_network'],
        ]);
        $premiumUser = User::factory()->create(['first_name' => 'Premium', 'last_name' => 'User', 'email' => 'premium@test.com']);
        $premiumUser->assignRole($this->userRole);
        Subscription::create([
            'user_id' => $premiumUser->id,
            'plan_id' => $premiumPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/user-manage/users?type=basic');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_users', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $basicUser->id)
            ->assertJsonPath('data.0.email', 'basic@test.com')
            ->assertJsonPath('data.0.tier', 'basic');
    }

    public function test_admin_can_list_premium_users(): void
    {
        $premiumPlan = Plan::create([
            'name' => 'Pro User',
            'billing_rate' => 19.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'plan_type' => PlanType::PREMIUM,
            'features' => ['build_network'],
        ]);

        $premiumUser = User::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Wonder',
            'email' => 'alice@test.com',
            'is_verified' => true,
        ]);
        $premiumUser->assignRole($this->userRole);

        Subscription::create([
            'user_id' => $premiumUser->id,
            'plan_id' => $premiumPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addDays(20),
        ]);

        $basicUser = User::factory()->create(['email' => 'basic@test.com']);
        $basicUser->assignRole($this->userRole);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/user-manage/users?type=premium');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_users', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $premiumUser->id)
            ->assertJsonPath('data.0.plan.name', 'Pro User')
            ->assertJsonPath('data.0.tier', 'premium');
    }

    public function test_admin_can_list_pro_industry_users(): void
    {
        $industryPlan = Plan::create([
            'name' => 'Pro Industries',
            'billing_rate' => 49.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'plan_type' => PlanType::INDUSTRY,
            'features' => ['company_profile'],
        ]);

        $industryOwner = User::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Builder',
            'email' => 'bob@company.com',
            'is_verified' => true,
        ]);
        $industryOwner->assignRole($this->userRole);

        Subscription::create([
            'user_id' => $industryOwner->id,
            'plan_id' => $industryPlan->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $industry = Industry::create([
            'name' => 'Acme Pharma',
            'slug' => 'acme-pharma',
            'created_by' => $industryOwner->id,
            'website' => 'https://acme.com',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/user-manage/users?type=pro_industry');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('tier', 'pro_industry')
            ->assertJsonPath('total_users', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $industryOwner->id)
            ->assertJsonPath('data.0.company.name', 'Acme Pharma')
            ->assertJsonPath('data.0.company.slug', 'acme-pharma')
            ->assertJsonPath('data.0.tier', 'pro_industry');

        // Verify type=industry works as an alias
        $aliasResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/user-manage/users?type=industry');

        $aliasResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('tier', 'pro_industry')
            ->assertJsonPath('total_users', 1);
    }

    public function test_admin_can_filter_users_by_search_and_status(): void
    {
        $user1 = User::factory()->create([
            'first_name' => 'David',
            'last_name' => 'Miller',
            'email' => 'david@example.com',
            'is_suspended' => false,
        ]);
        $user1->assignRole($this->userRole);

        $user2 = User::factory()->create([
            'first_name' => 'Diana',
            'last_name' => 'Prince',
            'email' => 'diana@example.com',
            'is_suspended' => true,
        ]);
        $user2->assignRole($this->userRole);

        // Search test
        $responseSearch = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/user-manage/users?type=basic&search=David');
        $responseSearch->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'david@example.com');

        // Status test (suspended only)
        $responseSuspended = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/user-manage/users?type=basic&status=suspended');
        $responseSuspended->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'diana@example.com');
    }

    public function test_admin_can_view_detailed_user_data(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'mobile' => '+1234567890',
            'title' => 'Clinical Psychologist',
            'is_verified' => true,
        ]);
        $user->assignRole($this->userRole);

        $skill = Skill::create(['name' => 'Cognitive Therapy']);

        UserProfile::create([
            'user_id' => $user->id,
            'about' => 'Experienced clinician',
            'country' => 'USA',
            'address' => '123 Main St',
            'postal_code' => '90210',
            'skills_id' => [$skill->id],
            'profession' => ['Psychologist'],
        ]);

        $company = Company::create(['name' => 'Health Corp']);
        Experience::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'title' => 'Senior Therapist',
            'is_current' => true,
            'start_date' => '2023-01-01',
        ]);

        $institution = Institution::create(['name' => 'Harvard University']);
        Education::create([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'degree' => 'PhD',
            'field_study' => 'Clinical Psychology',
            'start_month' => '09',
            'start_year' => '2018',
            'is_current' => false,
        ]);

        Post::create([
            'user_id' => $user->id,
            'description' => 'First post',
            'visibility' => 'public',
        ]);

        $otherUser = User::factory()->create();
        Connection::create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'status' => Connection::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/user-manage/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.last_name', 'Doe')
            ->assertJsonPath('data.title', 'Clinical Psychologist')
            ->assertJsonPath('data.profile.about', 'Experienced clinician')
            ->assertJsonPath('data.profile.skills.0.name', 'Cognitive Therapy')
            ->assertJsonPath('data.about', 'Experienced clinician')
            ->assertJsonPath('data.total_connections', 1)
            ->assertJsonPath('data.current_company.name', 'Health Corp')
            ->assertJsonPath('data.graduated_institution.name', 'Harvard University')
            ->assertJsonPath('data.activity.total_posts', 1)
            ->assertJsonPath('data.activity.posts.0.description', 'First post')
            ->assertJsonPath('data.experiences.0.title', 'Senior Therapist')
            ->assertJsonPath('data.experiences.0.company_name', 'Health Corp')
            ->assertJsonPath('data.experiences.0.company.name', 'Health Corp')
            ->assertJsonPath('data.educations.0.degree', 'PhD')
            ->assertJsonPath('data.educations.0.institution_name', 'Harvard University')
            ->assertJsonPath('data.educations.0.institution.name', 'Harvard University')
            ->assertJsonPath('data.stats.posts_count', 1)
            ->assertJsonPath('data.stats.connections_count', 1);
    }

    public function test_admin_can_toggle_suspend_user(): void
    {
        $user = User::factory()->create([
            'email' => 'tobe.suspended@example.com',
            'is_suspended' => false,
        ]);
        $user->assignRole($this->userRole);

        LoginActivity::create([
            'user_id' => $user->id,
            'token_id' => 'sample_token_jti',
            'login_at' => now(),
            'is_active' => true,
        ]);

        // Toggle to suspend
        $responseSuspend = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/user-manage/users/{$user->id}/toggle-suspend");

        $responseSuspend->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_suspended', true)
            ->assertJsonPath('data.status', 'suspended');

        $user->refresh();
        $this->assertTrue($user->is_suspended);
        $this->assertNotNull($user->suspended_at);
        $this->assertFalse(LoginActivity::where('user_id', $user->id)->first()->is_active);

        // Toggle to unsuspend
        $responseUnsuspend = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/user-manage/users/{$user->id}/toggle-suspend");

        $responseUnsuspend->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_suspended', false)
            ->assertJsonPath('data.status', 'active');

        $user->refresh();
        $this->assertFalse($user->is_suspended);
        $this->assertNull($user->suspended_at);
    }

    public function test_admin_can_explicitly_suspend_and_unsuspend(): void
    {
        $user = User::factory()->create(['is_suspended' => false]);
        $user->assignRole($this->userRole);

        // Explicit suspend via payload
        $resSuspend = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/user-manage/users/{$user->id}/toggle-suspend", ['is_suspended' => true]);

        $resSuspend->assertOk()
            ->assertJsonPath('data.is_suspended', true);

        $this->assertTrue($user->fresh()->is_suspended);

        // Explicit unsuspend via payload
        $resUnsuspend = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/user-manage/users/{$user->id}/toggle-suspend", ['is_suspended' => false]);

        $resUnsuspend->assertOk()
            ->assertJsonPath('data.is_suspended', false);

        $this->assertFalse($user->fresh()->is_suspended);
    }

    public function test_admin_cannot_suspend_admin(): void
    {
        $anotherAdmin = User::factory()->create(['email' => 'admin2@gmail.com']);
        $anotherAdmin->assignRole($this->adminRole);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/user-manage/users/{$anotherAdmin->id}/toggle-suspend");

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Admin accounts cannot be suspended.');
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'blocked@example.com',
            'password' => bcrypt('password123'),
            'is_verified' => true,
            'is_suspended' => true,
        ]);
        $user->assignRole($this->userRole);

        $response = $this->postJson('/api/login', [
            'email' => 'blocked@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account has been suspended. Please contact support.');
    }
}
