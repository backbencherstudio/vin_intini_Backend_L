<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stripe\Subscription as StripeSubscription;
use Tests\TestCase;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->admin = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'User']);
        $this->admin->assignRole($role);
    }

    public function test_admin_can_list_subscriptions_with_billing_details(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
        ]);

        $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
            'current_period_end' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->admin, 'api')->getJson('/api/admin/subscriptions');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subscriber.name', 'John Doe')
            ->assertJsonPath('data.0.plan.name', 'Pro')
            ->assertJsonPath('data.0.plan.amount', '9.99')
            ->assertJsonPath('data.0.plan.billing_cycle', 'monthly')
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.0.days_left', 10);
    }

    public function test_admin_can_filter_subscriptions_by_status(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
        ]);

        $user = User::factory()->create();

        Subscription::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1', 'status' => 'active',
        ]);
        Subscription::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'platform' => 'stripe',
            'provider_subscription_id' => 'sub_2', 'status' => 'canceled',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/subscriptions?status=canceled');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'canceled')
            ->assertJsonPath('data.0.next_billing_date', null);
    }

    public function test_admin_can_cancel_subscription_at_period_end(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
        ]);

        $user = User::factory()->create();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('cancelSubscription')->once()
                ->with('sub_1', true)
                ->andReturn(StripeSubscription::constructFrom(['id' => 'sub_1', 'cancel_at_period_end' => true]));
        });

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/subscriptions/{$subscription->id}/cancel");

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'cancel_at_period_end' => true,
            'status' => 'active',
        ]);
    }

    public function test_admin_cannot_cancel_already_canceled_subscription(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
        ]);

        $user = User::factory()->create();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'canceled',
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('cancelSubscription')->never();
        });

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/subscriptions/{$subscription->id}/cancel");

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
