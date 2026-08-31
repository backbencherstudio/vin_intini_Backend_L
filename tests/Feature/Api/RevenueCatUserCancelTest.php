<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\RevenueCatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RevenueCatUserCancelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $this->user = User::factory()->create(['is_verified' => true]);
        $this->user->assignRole($role);
        UserProfile::create(['user_id' => $this->user->id]);
    }

    private function makePlan(): Plan
    {
        return Plan::create([
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['search_profiles'],
            'revenuecat_product_id' => 'rc_prod_pro',
            'revenuecat_entitlement_id' => 'premium',
        ]);
    }

    public function test_user_can_cancel_own_revenuecat_subscription(): void
    {
        $plan = $this->makePlan();
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'platform' => 'revenuecat',
            'provider_subscription_id' => 'orig_1',
            'provider_customer_id' => (string) $this->user->id,
            'product_id' => 'rc_prod_pro',
            'status' => 'active',
        ]);

        $this->mock(RevenueCatService::class, function (MockInterface $mock) {
            $mock->shouldReceive('appUserIdFor')->andReturn('1');
            $mock->shouldReceive('revokeEntitlements')
                ->once()
                ->with(\Mockery::any(), ['premium'])
                ->andReturn([]);
        });

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/subscriptions/{$subscription->id}/cancel");

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'canceled',
        ]);
    }

    public function test_user_cannot_cancel_subscription_they_do_not_own(): void
    {
        $plan = $this->makePlan();
        $other = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $other->id,
            'plan_id' => $plan->id,
            'platform' => 'revenuecat',
            'provider_subscription_id' => 'orig_2',
            'provider_customer_id' => (string) $other->id,
            'product_id' => 'rc_prod_pro',
            'status' => 'active',
        ]);

        $this->actingAs($this->user, 'api')
            ->postJson("/api/subscriptions/{$subscription->id}/cancel")
            ->assertStatus(403);
    }
}
