<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TransactionManagementTest extends TestCase
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

    private function makePlan(string $name = 'Pro', string $cycle = 'monthly', float $rate = 9.99): Plan
    {
        return Plan::create([
            'name' => $name,
            'billing_rate' => $rate,
            'billing_cycle' => $cycle,
            'status' => 'active',
            'features' => ['search_profiles'],
        ]);
    }

    public function test_admin_can_list_transactions(): void
    {
        $plan = $this->makePlan();
        $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $transaction = Transaction::create([
            'provider_transaction_id' => 'pi_123',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => 9.99,
            'currency' => 'usd',
            'card_brand' => 'visa',
            'card_last4' => '4242',
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'api')->getJson('/api/admin/transactions');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.transaction_id', 'pi_123')
            ->assertJsonPath('data.0.subscriber.name', 'John Doe')
            ->assertJsonPath('data.0.plan.name', 'Pro')
            ->assertJsonPath('data.0.plan.billing_cycle', 'monthly')
            ->assertJsonPath('data.0.amount', '9.99')
            ->assertJsonPath('data.0.status', 'succeeded')
            ->assertJsonPath('data.0.card_brand', 'visa')
            ->assertJsonPath('data.0.card_last4', '4242');
    }

    public function test_admin_can_filter_transactions_by_status_and_plan(): void
    {
        $pro = $this->makePlan('Pro');
        $free = $this->makePlan('Free', 'monthly', 0);
        $user = User::factory()->create();

        Transaction::create([
            'provider_transaction_id' => 'pi_1', 'user_id' => $user->id, 'plan_id' => $pro->id,
            'amount' => 9.99, 'status' => 'succeeded', 'paid_at' => now(),
        ]);
        Transaction::create([
            'provider_transaction_id' => 'pi_2', 'user_id' => $user->id, 'plan_id' => $free->id,
            'amount' => 0, 'status' => 'failed',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/transactions?status=failed&plan_id='.$free->id);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.transaction_id', 'pi_2');
    }

    public function test_admin_can_search_transactions_by_subscriber_name(): void
    {
        $plan = $this->makePlan();
        $john = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $jane = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        Transaction::create([
            'provider_transaction_id' => 'pi_1', 'user_id' => $john->id, 'plan_id' => $plan->id,
            'amount' => 9.99, 'status' => 'succeeded', 'paid_at' => now(),
        ]);
        Transaction::create([
            'provider_transaction_id' => 'pi_2', 'user_id' => $jane->id, 'plan_id' => $plan->id,
            'amount' => 9.99, 'status' => 'succeeded', 'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/transactions?search=Jane');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subscriber.name', 'Jane Smith');
    }

    public function test_admin_gets_overview_with_previous_month_comparison(): void
    {
        $plan = $this->makePlan();
        $user = User::factory()->create();

        Transaction::create([
            'provider_transaction_id' => 'pi_1', 'user_id' => $user->id, 'plan_id' => $plan->id,
            'amount' => 10, 'status' => 'succeeded', 'paid_at' => now()->subMonthNoOverflow(),
        ]);
        Transaction::create([
            'provider_transaction_id' => 'pi_2', 'user_id' => $user->id, 'plan_id' => $plan->id,
            'amount' => 20, 'status' => 'succeeded', 'paid_at' => now(),
        ]);

        $lastMonthSub = Subscription::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1', 'status' => 'active',
        ]);
        $lastMonthSub->created_at = now()->subMonthNoOverflow();
        $lastMonthSub->save();

        Subscription::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'platform' => 'stripe',
            'provider_subscription_id' => 'sub_2', 'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'api')->getJson('/api/admin/transactions/overview');

        $response->assertOk()->assertJsonPath('success', true);

        $cards = collect($response->json('data'))->keyBy('label');

        $this->assertEquals(1, $cards['Subscribers']['value']);
        $this->assertEquals(1, $cards['Subscribers']['previous_value']);
        $this->assertEquals(0.0, $cards['Subscribers']['change_percent']);

        $this->assertEquals(20.0, $cards['Total Revenue']['value']);
        $this->assertEquals(10.0, $cards['Total Revenue']['previous_value']);
        $this->assertEquals(100.0, $cards['Total Revenue']['change_percent']);

        $this->assertEquals(1, $cards['Successful Payments']['value']);
        $this->assertEquals(1, $cards['Successful Payments']['previous_value']);
        $this->assertEquals(0.0, $cards['Successful Payments']['change_percent']);
        $this->assertEquals('flat', $cards['Successful Payments']['direction']);

        $this->assertEquals(0, $cards['Failed Payments']['value']);
        $this->assertEquals(0, $cards['Refunded Amount']['value']);
    }
}
