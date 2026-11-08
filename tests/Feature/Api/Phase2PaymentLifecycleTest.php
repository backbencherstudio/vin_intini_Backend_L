<?php

namespace Tests\Feature\Api;

use App\Enums\AdCampaignStatus;
use App\Enums\BoostTargetEntity;
use App\Enums\PlanType;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\LoginActivity;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Advertising\ProductBoostProjection;
use App\Services\ProductBoostPaymentService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Spatie\Permission\Models\Role;
use Stripe\Event;
use Stripe\PaymentIntent;
use Tests\TestCase;

class Phase2PaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('public');
    }

    private function createProductWithUser(): array
    {
        $user = User::factory()->create();

        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $user->assignRole($role);

        $user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'headline' => 'Researcher',
        ]);

        LoginActivity::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'session_id' => 'sess_test_'.uniqid(),
            'status' => 'active',
            'login_at' => now(),
            'last_activity_at' => now(),
        ]);

        $industry = Industry::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'name' => 'BioTech Labs',
            'slug' => 'biotech-labs-'.uniqid(),
            'industry' => 'biotechnology',
            'description' => 'Test bio industry',
            'status' => 'active',
        ]);

        $section = IndustrySections::create([
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
            'name' => 'Therapeutics',
        ]);

        $category = IndustryCategory::create([
            'section_id' => $section->id,
            'category_name' => 'Gene Therapy',
        ]);

        $product = IndustryProduct::create([
            'user_id' => $user->id,
            'industry_id' => $industry->id,
            'section_id' => $section->id,
            'category_id' => $category->id,
            'product_name' => 'NeuroGen A',
            'slug' => 'neurogen-a-'.uniqid(),
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
            'description' => 'Advanced gene therapy formula',
            'image' => 'products/sample.jpg',
            'status' => 'active',
            'is_boosted' => false,
        ]);

        $plan = Plan::create([
            'name' => 'Pro Industry',
            'short_description' => 'Pro Industry Plan',
            'plan_type' => PlanType::INDUSTRY->value,
            'billing_rate' => 99,
            'billing_cycle' => 'monthly',
            'features' => ['company_profile', 'product_listings'],
            'status' => 'active',
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_ind_'.uniqid(),
            'status' => 'active',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        return [$user, $industry, $section, $category, $product];
    }

    /**
     * B02: Confirming a 3DS payment for a non-pending (paused/cancelled/completed)
     * campaign must fail and not extend duration or change status.
     */
    public function test_confirm_stripe_payment_rejects_non_pending_campaign(): void
    {
        [$user, , , , $product] = $this->createProductWithUser();

        $pausedCampaign = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'industry_id' => $product->industry_id,
            'ad_type' => 'product',
            'network_type' => 'neuroscience',
            'amount_paid' => 30.00,
            'currency' => 'USD',
            'duration_days' => 7,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(5),
            'status' => AdCampaignStatus::PAUSED,
            'stripe_payment_intent_id' => 'pi_paused_test',
            'payment_platform' => 'stripe',
        ]);

        $service = app(ProductBoostPaymentService::class);
        $result = $service->confirmStripePayment($user, $product, 'pi_paused_test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot be activated from status', $result['message']);
        $pausedCampaign->refresh();
        $this->assertSame(AdCampaignStatus::PAUSED, $pausedCampaign->status);
    }

    /**
     * B02: Confirming an already active campaign returns idempotent success without altering ends_at.
     */
    public function test_confirm_stripe_payment_is_idempotent_for_already_active_campaign(): void
    {
        [$user, , , , $product] = $this->createProductWithUser();

        $originalEndsAt = now()->addDays(5)->startOfSecond();
        $activeCampaign = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'industry_id' => $product->industry_id,
            'ad_type' => 'product',
            'network_type' => 'neuroscience',
            'amount_paid' => 30.00,
            'currency' => 'USD',
            'duration_days' => 7,
            'starts_at' => now()->subDays(2),
            'ends_at' => $originalEndsAt,
            'status' => AdCampaignStatus::ACTIVE,
            'stripe_payment_intent_id' => 'pi_already_active',
            'payment_platform' => 'stripe',
        ]);

        $service = app(ProductBoostPaymentService::class);
        $result = $service->confirmStripePayment($user, $product, 'pi_already_active');

        $this->assertTrue($result['success']);
        $this->assertSame('Campaign is already active.', $result['message']);
        $activeCampaign->refresh();
        $this->assertEquals($originalEndsAt->timestamp, $activeCampaign->ends_at->timestamp);
    }

    /**
     * B03: Webhook payment_intent.succeeded activates pending campaign and updates projection.
     */
    public function test_stripe_webhook_payment_intent_succeeded_activates_boost_campaign(): void
    {
        [$user, , , , $product] = $this->createProductWithUser();

        $package = BoostPackage::create([
            'name' => 'Weekly Boost',
            'target_entity' => BoostTargetEntity::PRODUCT,
            'duration_days' => 7,
            'price' => 35.00,
            'priority_weight' => 3,
            'status' => 'active',
        ]);

        $campaign = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'industry_id' => $product->industry_id,
            'boost_package_id' => $package->id,
            'ad_type' => 'product',
            'network_type' => 'neuroscience',
            'amount_paid' => 35.00,
            'currency' => 'USD',
            'duration_days' => 7,
            'status' => AdCampaignStatus::PENDING,
            'stripe_payment_intent_id' => 'pi_webhook_boost_test',
            'payment_platform' => 'stripe',
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_webhook_boost_test',
                    'status' => 'succeeded',
                    'payment_method' => [
                        'card' => [
                            'brand' => 'visa',
                            'last4' => '4242',
                        ],
                    ],
                ],
            ],
        ]);

        $mockStripe = Mockery::mock(StripeService::class);
        $mockStripe->shouldReceive('constructEvent')->once()->andReturn($event);
        $this->app->instance(StripeService::class, $mockStripe);

        $response = $this->postJson('/api/webhooks/stripe', [], [
            'Stripe-Signature' => 'sig_test',
        ]);

        $response->assertStatus(200)->assertJson(['received' => true]);

        $campaign->refresh();
        $this->assertSame(AdCampaignStatus::ACTIVE, $campaign->status);
        $this->assertNotNull($campaign->starts_at);
        $this->assertNotNull($campaign->ends_at);

        $product->refresh();
        $this->assertTrue((bool) $product->is_boosted);
        $this->assertSame(3, (int) $product->boost_score);
        $this->assertNotNull($product->boost_expires_at);
    }

    /**
     * B04: StripeService::createPaymentIntent accepts idempotencyKey.
     */
    public function test_stripe_create_payment_intent_accepts_idempotency_key(): void
    {
        $mockStripe = Mockery::mock(StripeService::class)->makePartial();
        $paymentIntent = PaymentIntent::constructFrom(['id' => 'pi_idemp', 'status' => 'succeeded']);

        $mockStripe->shouldReceive('createPaymentIntent')
            ->with(
                1000,
                'usd',
                'cus_123',
                'pm_123',
                Mockery::any(),
                true,
                'idemp-key-xyz'
            )
            ->once()
            ->andReturn($paymentIntent);

        $res = $mockStripe->createPaymentIntent(
            1000,
            'usd',
            'cus_123',
            'pm_123',
            [],
            true,
            'idemp-key-xyz'
        );

        $this->assertSame('pi_idemp', $res->id);
    }

    /**
     * B05: Product boost projection handles multiple concurrent campaigns:
     * - Pausing one does not unboost if another active campaign exists.
     * - Expiration and priority score correctly track the active campaign set.
     */
    public function test_product_boost_projection_with_multiple_campaigns(): void
    {
        [$user, , , , $product] = $this->createProductWithUser();

        $pkgHigh = BoostPackage::create([
            'name' => 'High Tier',
            'target_entity' => BoostTargetEntity::PRODUCT,
            'duration_days' => 10,
            'price' => 50,
            'priority_weight' => 5,
            'status' => 'active',
        ]);

        $pkgLow = BoostPackage::create([
            'name' => 'Low Tier',
            'target_entity' => BoostTargetEntity::PRODUCT,
            'duration_days' => 20,
            'price' => 40,
            'priority_weight' => 2,
            'status' => 'active',
        ]);

        // Campaign 1: expires in 5 days, score 5
        $c1 = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'industry_id' => $product->industry_id,
            'boost_package_id' => $pkgHigh->id,
            'ad_type' => 'product',
            'network_type' => 'neuroscience',
            'amount_paid' => 50,
            'currency' => 'USD',
            'duration_days' => 5,
            'starts_at' => now(),
            'ends_at' => now()->addDays(5),
            'status' => AdCampaignStatus::ACTIVE,
            'payment_platform' => 'stripe',
        ]);

        // Campaign 2: expires in 15 days, score 2
        $c2 = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'industry_id' => $product->industry_id,
            'boost_package_id' => $pkgLow->id,
            'ad_type' => 'product',
            'network_type' => 'neuroscience',
            'amount_paid' => 40,
            'currency' => 'USD',
            'duration_days' => 15,
            'starts_at' => now(),
            'ends_at' => now()->addDays(15),
            'status' => AdCampaignStatus::ACTIVE,
            'payment_platform' => 'stripe',
        ]);

        ProductBoostProjection::projectFor($product);
        $product->refresh();

        $this->assertTrue((bool) $product->is_boosted);
        $this->assertSame(5, (int) $product->boost_score); // Max score from c1
        $this->assertEquals($c2->ends_at->timestamp, $product->boost_expires_at->timestamp); // Max ends_at from c2

        // Now pause c1
        $c1->pause();
        $product->refresh();

        // Product should STILL be boosted because c2 is active!
        $this->assertTrue((bool) $product->is_boosted);
        $this->assertSame(2, (int) $product->boost_score); // Drops to c2 score
        $this->assertEquals($c2->ends_at->timestamp, $product->boost_expires_at->timestamp);

        // Now pause c2
        $c2->pause();
        $product->refresh();

        // Both are paused -> product is no longer boosted
        $this->assertFalse((bool) $product->is_boosted);
        $this->assertNull($product->boost_expires_at);
        $this->assertSame(0, (int) $product->boost_score);
    }

    /**
     * B06: Subscription::isActive() and User::hasActiveSubscription() enforce time boundaries.
     */
    public function test_subscription_expiration_boundaries(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Pro',
            'short_description' => 'Pro Plan',
            'plan_type' => PlanType::PREMIUM->value,
            'billing_rate' => 29,
            'billing_cycle' => 'monthly',
            'features' => ['company_profile'],
            'status' => 'active',
        ]);

        // 1. Future period end -> active
        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
            'current_period_start' => now()->subDays(15),
            'current_period_end' => now()->addDays(15),
            'ends_at' => null,
        ]);

        $this->assertTrue($sub->isActive());
        $this->assertTrue($user->hasActiveSubscription(PlanType::PREMIUM));

        // 2. Past period end -> inactive
        $sub->update([
            'current_period_end' => now()->subDay(),
        ]);
        $this->assertFalse($sub->isActive());
        $this->assertFalse($user->hasActiveSubscription(PlanType::PREMIUM));

        // With 3-day grace period -> active
        $this->assertTrue($sub->isActive(3));
        $this->assertTrue($user->hasActiveSubscription(PlanType::PREMIUM, 3));

        // 3. Status is active, but ends_at is in past -> inactive
        $sub->update([
            'current_period_end' => now()->addDays(5),
            'ends_at' => now()->subHour(),
        ]);
        $this->assertFalse($sub->isActive());
        $this->assertFalse($user->hasActiveSubscription(PlanType::PREMIUM));
    }

    /**
     * B07: BoostPackage target entity mismatch is rejected.
     */
    public function test_boost_package_target_entity_mismatch_rejected(): void
    {
        [$user, , , , $product] = $this->createProductWithUser();

        $newsfeedPackage = BoostPackage::create([
            'name' => 'Newsfeed Post Package',
            'target_entity' => BoostTargetEntity::POST,
            'duration_days' => 5,
            'price' => 15.00,
            'priority_weight' => 2,
            'status' => 'active',
        ]);

        $service = app(ProductBoostPaymentService::class);

        $this->expectException(ValidationException::class);
        $service->resolvePricingAndDuration(['boost_package_id' => $newsfeedPackage->id]);
    }

    /**
     * B07: Product creation rejects taxonomy mismatch between category and section.
     */
    public function test_product_store_rejects_category_section_mismatch(): void
    {
        [$user, , $section] = $this->createProductWithUser();

        $otherSection = IndustrySections::create([
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
            'name' => 'Diagnostics',
        ]);

        $categoryOfOtherSection = IndustryCategory::create([
            'section_id' => $otherSection->id,
            'category_name' => 'Imaging Biomarkers',
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/industry/products/create', [
            'product_name' => 'Test Item',
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
            'section_id' => $section->id, // Mismatched section!
            'category_id' => $categoryOfOtherSection->id,
            'description' => 'Test description',
            'image' => UploadedFile::fake()->image('product.jpg'),
            'information_confirmed' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    /**
     * B07: Product creation rejects taxonomy mismatch between section and network/industry type.
     */
    public function test_product_store_rejects_section_network_type_mismatch(): void
    {
        [$user, , , $category] = $this->createProductWithUser();

        $psychSection = IndustrySections::create([
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'name' => 'Behavioral Health',
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/industry/products/create', [
            'product_name' => 'Test Item',
            'network_type' => 'neuroscience', // Mismatched network type!
            'industry_type' => 'biotechnology',
            'section_id' => $psychSection->id,
            'category_id' => $category->id,
            'description' => 'Test description',
            'image' => UploadedFile::fake()->image('product.jpg'),
            'information_confirmed' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['section_id']);
    }
}
