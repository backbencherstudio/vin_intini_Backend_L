<?php

namespace Tests\Feature\Auth;

use App\Models\Industry;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MeCompanyPageFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_reports_the_company_page_can_be_created_for_an_eligible_user(): void
    {
        $user = $this->makeUser();
        $this->subscribe($user);

        $response = $this->actingAs($user, 'api')->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('industry.can_create_company_page', true)
            ->assertJsonPath('industry.has_company', false)
            ->assertJsonPath('industry.company', null)
            ->assertJsonMissingPath('user.can_create_company_page')
            ->assertJsonMissingPath('user.company_id');
    }

    public function test_me_reports_can_create_company_page_as_false_for_a_trialing_subscription(): void
    {
        $user = $this->makeUser();
        $this->subscribe($user, ['status' => 'trialing']);

        $response = $this->actingAs($user, 'api')->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('industry.can_create_company_page', false)
            ->assertJsonPath('industry.has_company', false)
            ->assertJsonPath('subscription.is_subscribed', true);
    }

    public function test_me_reports_can_create_company_page_as_false_when_the_period_has_ended(): void
    {
        $user = $this->makeUser();
        $this->subscribe($user, ['current_period_end' => now()->subDay()]);

        $response = $this->actingAs($user, 'api')->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('industry.can_create_company_page', false)
            ->assertJsonPath('industry.has_company', false)
            ->assertJsonPath('subscription.is_subscribed', true);
    }

    public function test_me_reports_can_create_company_page_as_false_when_the_plan_lacks_the_feature(): void
    {
        $user = $this->makeUser();
        $this->subscribe($user, features: ['build_network']);

        $response = $this->actingAs($user, 'api')->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('industry.can_create_company_page', false)
            ->assertJsonPath('industry.has_company', false);
    }

    public function test_me_reports_can_create_company_page_as_false_when_the_plan_is_inactive(): void
    {
        $user = $this->makeUser();
        $subscription = $this->subscribe($user);
        $subscription->plan->update(['status' => 'inactive']);

        $response = $this->actingAs($user, 'api')->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('industry.can_create_company_page', false)
            ->assertJsonPath('industry.has_company', false);
    }

    public function test_me_reports_can_create_company_page_as_false_when_the_user_already_owns_a_page(): void
    {
        $user = $this->makeUser();
        $this->subscribe($user);
        $industry = Industry::create([
            'name' => 'Bright Labs',
            'slug' => 'bright-labs',
            'industry' => 'Software',
            'created_by' => $user->id,
            'authorization_confirmed' => true,
            'authorization_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('industry.can_create_company_page', false)
            ->assertJsonPath('industry.has_company', true)
            ->assertJsonPath('industry.company.id', $industry->id)
            ->assertJsonPath('industry.company.name', 'Bright Labs')
            ->assertJsonMissingPath('user.can_create_company_page')
            ->assertJsonMissingPath('user.company_id');
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
            'first_name' => 'Mahmudul',
            'last_name' => 'Hasan',
        ]);

        $user->assignRole($role);

        UserProfile::create(['user_id' => $user->id]);

        return $user;
    }

    private function subscribe(User $user, array $overrides = [], ?array $features = null): Subscription
    {
        $plan = Plan::create([
            'name' => 'Company Plan',
            'billing_rate' => 10,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => $features ?? ['company_profile'],
        ]);

        return Subscription::create(array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_'.$user->id,
            'provider_customer_id' => (string) $user->id,
            'product_id' => 'prod_company',
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ], $overrides));
    }
}
