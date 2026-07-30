<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\PlanFeature;
use App\Models\Plan;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stripe\Price;
use Stripe\Product;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createProduct')->andReturn(Product::constructFrom(['id' => 'prod_mock']));
            $mock->shouldReceive('createPrice')->andReturn(Price::constructFrom(['id' => 'price_mock']));
            $mock->shouldReceive('updateProduct')->andReturn(Product::constructFrom(['id' => 'prod_mock']));
            $mock->shouldReceive('archivePrice')->andReturn(Price::constructFrom(['id' => 'price_mock']));
            $mock->shouldReceive('archiveProduct')->andReturn(Product::constructFrom(['id' => 'prod_mock']));
        });
    }

    public function test_admin_can_list_plans_when_empty(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/plans');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_admin_can_list_plans(): void
    {
        Plan::create(['name' => 'Plan A', 'billing_rate' => 10, 'billing_cycle' => 'monthly', 'status' => 'active', 'features' => ['search_profiles']]);
        Plan::create(['name' => 'Plan B', 'billing_rate' => 20, 'billing_cycle' => 'yearly', 'status' => 'active', 'features' => ['search_profiles']]);
        Plan::create(['name' => 'Plan C', 'billing_rate' => 30, 'billing_cycle' => 'monthly', 'status' => 'active', 'features' => ['search_profiles']]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/plans');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_plan(): void
    {
        $payload = [
            'name' => 'Premium Plan',
            'short_description' => 'Our premium subscription',
            'billing_rate' => 29.99,
            'billing_cycle' => 'monthly',
            'discount_percent' => 10,
            'discount_duration' => '2026-12-31',
            'badge_color' => '#FF5733',
            'status' => 'active',
            'features' => [
                PlanFeature::SEARCH_PROFILES->value,
                PlanFeature::UNLIMITED_MESSAGING->value,
            ],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/plans/create', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Premium Plan')
            ->assertJsonPath('data.billing_rate', '29.99')
            ->assertJsonPath('data.billing_cycle', 'monthly')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.stripe_product_id', 'prod_mock')
            ->assertJsonPath('data.stripe_price_id', 'price_mock');

        $this->assertDatabaseHas('plans', [
            'name' => 'Premium Plan',
            'stripe_product_id' => 'prod_mock',
            'stripe_price_id' => 'price_mock',
        ]);
    }

    public function test_create_plan_validates_features_against_enum(): void
    {
        $payload = [
            'name' => 'Bad Plan',
            'billing_rate' => 10,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['invalid_feature'],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/plans/create', $payload);

        $response->assertUnprocessable();
    }

    public function test_create_plan_validates_billing_cycle(): void
    {
        $payload = [
            'name' => 'Bad Plan',
            'billing_rate' => 10,
            'billing_cycle' => 'weekly',
            'status' => 'active',
            'features' => [PlanFeature::SEARCH_PROFILES->value],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/plans/create', $payload);

        $response->assertUnprocessable();
    }

    public function test_admin_can_update_plan_name(): void
    {
        $plan = Plan::create(['name' => 'Old Name', 'billing_rate' => 10, 'billing_cycle' => 'monthly', 'status' => 'active', 'features' => ['search_profiles'], 'stripe_product_id' => 'prod_mock', 'stripe_price_id' => 'price_mock']);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson('/api/admin/plans/'.$plan->id, [
                'name' => 'New Name',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_admin_can_update_plan_billing_rate_creates_new_stripe_price(): void
    {
        $plan = Plan::create(['name' => 'Plan', 'billing_rate' => 10.00, 'billing_cycle' => 'monthly', 'status' => 'active', 'features' => ['search_profiles'], 'stripe_product_id' => 'prod_mock', 'stripe_price_id' => 'price_mock']);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson('/api/admin/plans/'.$plan->id, [
                'billing_rate' => 20.00,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.billing_rate', '20.00');
    }

    public function test_admin_can_toggle_plan_status_to_inactive(): void
    {
        $plan = Plan::create(['name' => 'Plan', 'billing_rate' => 10, 'billing_cycle' => 'monthly', 'status' => 'active', 'features' => ['search_profiles'], 'stripe_product_id' => 'prod_mock', 'stripe_price_id' => 'price_mock']);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson('/api/admin/plans/'.$plan->id.'/status');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_admin_can_toggle_plan_status_to_active(): void
    {
        $plan = Plan::create(['name' => 'Plan', 'billing_rate' => 10, 'billing_cycle' => 'monthly', 'status' => 'inactive', 'features' => ['search_profiles'], 'stripe_product_id' => 'prod_mock', 'stripe_price_id' => 'price_mock']);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson('/api/admin/plans/'.$plan->id.'/status');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_admin_can_show_single_plan(): void
    {
        $plan = Plan::create(['name' => 'Enterprise', 'billing_rate' => 10, 'billing_cycle' => 'monthly', 'status' => 'active', 'features' => ['search_profiles'], 'stripe_product_id' => 'prod_mock', 'stripe_price_id' => 'price_mock']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/plans/'.$plan->id);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $plan->id)
            ->assertJsonPath('data.name', 'Enterprise');
    }

    public function test_admin_can_list_plan_features(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/plan-features');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(count(\App\Enums\PlanFeature::cases()), 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['value', 'label'],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_plans(): void
    {
        $response = $this->getJson('/api/admin/plans');
        $response->assertUnauthorized();
    }

    public function test_non_admin_user_cannot_access_plans(): void
    {
        $userRole = Role::create(['name' => 'user', 'guard_name' => 'api']);
        $user = User::factory()->create();
        $user->assignRole($userRole);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/admin/plans');

        $response->assertForbidden();
    }
}
