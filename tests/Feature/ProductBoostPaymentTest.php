<?php

use App\Enums\AdCampaignStatus;
use App\Enums\OtpType;
use App\Mail\BoostOtpMail;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\Otp;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

    $this->user = User::factory()->create(['is_verified' => true]);
    $this->user->assignRole($this->userRole);
    UserProfile::create(['user_id' => $this->user->id]);

    $this->otherUser = User::factory()->create(['is_verified' => true]);
    $this->otherUser->assignRole($this->userRole);
    UserProfile::create(['user_id' => $this->otherUser->id]);

    $this->industry = Industry::create([
        'created_by' => $this->user->id,
        'name' => 'NeuroTech Labs Inc.',
        'slug' => 'neurotech-labs-inc',
        'industry' => 'biotechnology',
    ]);

    $this->section = IndustrySections::create([
        'name' => 'Therapeutics',
        'industry_type' => 'biotechnology',
        'network_type' => 'neuroscience',
    ]);

    $this->category = IndustryCategory::create([
        'section_id' => $this->section->id,
        'category_name' => 'Nootropics',
    ]);

    $this->product = IndustryProduct::create([
        'industry_id' => $this->industry->id,
        'creator_id' => $this->user->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'neuroscience',
        'industry_type' => 'biotechnology',
        'product_name' => 'SynapseBoost Pro',
        'slug' => 'synapseboost-pro',
        'description' => 'Clinical cognitive amplifier',
        'status' => 'active',
        'is_boosted' => false,
    ]);

    $this->package = BoostPackage::create([
        'name' => '7-Day Pro Surge',
        'target_entity' => 'product',
        'duration_days' => 7,
        'price' => 19.99,
        'estimated_impressions' => '5,000 - 8,000',
        'badge' => 'Featured',
        'priority_weight' => 2,
        'status' => 'active',
        'revenuecat_store_identifier_ios' => 'boost_product_7d',
        'revenuecat_store_identifier_android' => 'boost_product_7d_gp',
        'revenuecat_entitlement_identifier' => 'product_boost_7d',
    ]);
});

function seedBoostOtp(int $userId, string $otp = '1234', ?Carbon $expiresAt = null): Otp
{
    return Otp::create([
        'user_id' => $userId,
        'type' => OtpType::BOOST->value,
        'otp' => $otp,
        'expires_at' => $expiresAt ?? now()->addMinutes(3),
    ]);
}

test('advertiser can view packages with stripe key and custom slider config', function () {
    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/industry/boost-packages')
        ->assertStatus(200);

    $response->assertJsonStructure([
        'success',
        'data' => [
            'stripe_public_key',
            'preset_packages',
            'custom_slider' => [
                'enabled',
                'daily_rate',
                'min_days',
                'max_days',
            ],
        ],
    ]);

    $response->assertJsonPath('data.preset_packages.0.id', $this->package->id);
    $response->assertJsonPath('data.preset_packages.0.checkout_type', 'revenuecat');
});

test('send otp dispatches email and rate limits subsequent requests', function () {
    Mail::fake();

    $response = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/send-otp", [
            'checkout_type' => 'stripe',
            'boost_package_id' => $this->package->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.requires_verification', true)
        ->assertJsonPath('data.email', $this->user->email);

    Mail::assertQueued(BoostOtpMail::class);

    $this->assertDatabaseHas('otps', [
        'user_id' => $this->user->id,
        'type' => OtpType::BOOST->value,
    ]);

    // Subsequent request within expiry returns 429 rate limit
    $rateLimitResponse = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/send-otp", [
            'checkout_type' => 'stripe',
            'boost_package_id' => $this->package->id,
        ]);

    $rateLimitResponse->assertStatus(429)
        ->assertJsonPath('success', false);
});

test('send otp with revenuecat returns 422 info message', function () {
    $response = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/send-otp", [
            'checkout_type' => 'revenuecat',
            'boost_package_id' => $this->package->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('boost purchase validates required fields including otp for stripe', function () {
    $response = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
            'checkout_type' => 'stripe',
            'boost_package_id' => $this->package->id,
            'payment_method' => 'pm_card_visa',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['otp']);
});

test('boost purchase with invalid or expired otp returns 400', function () {
    seedBoostOtp($this->user->id, '9999');

    $response = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
            'checkout_type' => 'stripe',
            'boost_package_id' => $this->package->id,
            'payment_method' => 'pm_card_visa',
            'otp' => '0000',
        ]);

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Invalid OTP.');
});

test('unauthorized user cannot boost someone else product', function () {
    $response = $this->actingAs($this->otherUser, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
            'checkout_type' => 'stripe',
            'boost_package_id' => $this->package->id,
            'payment_method' => 'pm_card_visa',
            'otp' => '1234',
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'You are not authorized to boost this product.');
});

test('advertiser can purchase boost with direct stripe elements card payment and otp', function () {
    seedBoostOtp($this->user->id, '1234');

    $this->mock(StripeService::class, function (MockInterface $mock) {
        $mock->shouldReceive('getOrCreateCustomer')->once()->andReturn(
            Customer::constructFrom(['id' => 'cus_mock_adv']),
        );
        $mock->shouldReceive('attachPaymentMethod')->once()
            ->with('pm_card_visa', 'cus_mock_adv')
            ->andReturn(PaymentMethod::constructFrom(['id' => 'pm_card_visa']));
        $mock->shouldReceive('createPaymentIntent')->once()
            ->with(
                1999,
                'usd',
                'cus_mock_adv',
                'pm_card_visa',
                Mockery::type('array'),
                true
            )
            ->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_mock_boost_123',
                'status' => 'succeeded',
                'amount' => 1999,
                'currency' => 'usd',
                'payment_method' => [
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                    ],
                ],
            ]));
    });

    $response = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
            'checkout_type' => 'stripe',
            'boost_package_id' => $this->package->id,
            'payment_method' => 'pm_card_visa',
            'network_type' => 'neuroscience',
            'otp' => '1234',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.requires_action', false)
        ->assertJsonPath('data.campaign.status', 'active')
        ->assertJsonPath('data.campaign.amount_paid', '19.99')
        ->assertJsonPath('data.product.is_boosted', true)
        ->assertJsonPath('data.product.badge', 'Sponsored');

    // Database assertions
    $this->assertDatabaseHas('ad_campaigns', [
        'boostable_id' => $this->product->id,
        'boostable_type' => IndustryProduct::class,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->package->id,
        'status' => AdCampaignStatus::ACTIVE->value,
        'stripe_payment_intent_id' => 'pi_mock_boost_123',
        'payment_platform' => 'stripe',
        'duration_days' => 7,
    ]);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $this->user->id,
        'provider_transaction_id' => 'pi_mock_boost_123',
        'status' => 'succeeded',
        'amount' => 19.99,
        'card_brand' => 'visa',
        'card_last4' => '4242',
    ]);

    // OTP was consumed and deleted
    $this->assertDatabaseMissing('otps', [
        'user_id' => $this->user->id,
        'type' => OtpType::BOOST->value,
    ]);

    $this->product->refresh();
    expect($this->product->is_boosted)->toBeTrue()
        ->and($this->product->boost_score)->toBe(2)
        ->and($this->product->boost_expires_at)->not->toBeNull();
});

test('stripe payment returning requires_action yields client secret and pending campaign', function () {
    seedBoostOtp($this->user->id, '1234');

    $this->mock(StripeService::class, function (MockInterface $mock) {
        $mock->shouldReceive('getOrCreateCustomer')->once()->andReturn(
            Customer::constructFrom(['id' => 'cus_mock_3ds']),
        );
        $mock->shouldReceive('attachPaymentMethod')->once()
            ->with('pm_card_threeDSecureRequired', 'cus_mock_3ds')
            ->andReturn(PaymentMethod::constructFrom(['id' => 'pm_card_threeDSecureRequired']));
        $mock->shouldReceive('createPaymentIntent')->once()
            ->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_3ds_challenge_456',
                'status' => 'requires_action',
                'client_secret' => 'pi_3ds_challenge_456_secret',
            ]));
    });

    $response = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
            'checkout_type' => 'stripe',
            'boost_package_id' => $this->package->id,
            'payment_method' => 'pm_card_threeDSecureRequired',
            'otp' => '1234',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.requires_action', true)
        ->assertJsonPath('data.payment_intent_client_secret', 'pi_3ds_challenge_456_secret')
        ->assertJsonPath('data.payment_status', 'requires_action');

    $this->assertDatabaseHas('ad_campaigns', [
        'boostable_id' => $this->product->id,
        'status' => AdCampaignStatus::PENDING->value,
        'stripe_payment_intent_id' => 'pi_3ds_challenge_456',
    ]);

    $this->assertDatabaseHas('transactions', [
        'provider_transaction_id' => 'pi_3ds_challenge_456',
        'status' => 'pending',
    ]);
});

test('advertiser can confirm 3ds payment intent after modal authentication', function () {
    // Seed pending campaign and transaction
    $campaign = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $this->product->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->package->id,
        'ad_type' => 'product',
        'network_type' => 'both',
        'amount_paid' => 19.99,
        'currency' => 'USD',
        'duration_days' => 7,
        'status' => AdCampaignStatus::PENDING,
        'stripe_payment_intent_id' => 'pi_3ds_confirmed_789',
        'payment_platform' => 'stripe',
    ]);

    Transaction::create([
        'user_id' => $this->user->id,
        'ad_campaign_id' => $campaign->id,
        'provider_transaction_id' => 'pi_3ds_confirmed_789',
        'amount' => 19.99,
        'currency' => 'usd',
        'status' => 'pending',
    ]);

    $this->mock(StripeService::class, function (MockInterface $mock) {
        $mock->shouldReceive('retrievePaymentIntent')
            ->once()
            ->with('pi_3ds_confirmed_789')
            ->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_3ds_confirmed_789',
                'status' => 'succeeded',
                'payment_method' => [
                    'card' => [
                        'brand' => 'mastercard',
                        'last4' => '8888',
                    ],
                ],
            ]));
    });

    $response = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/confirm", [
            'payment_intent_id' => 'pi_3ds_confirmed_789',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.campaign.status', 'active')
        ->assertJsonPath('data.product.is_boosted', true);

    $this->assertDatabaseHas('ad_campaigns', [
        'id' => $campaign->id,
        'status' => AdCampaignStatus::ACTIVE->value,
    ]);

    $this->assertDatabaseHas('transactions', [
        'provider_transaction_id' => 'pi_3ds_confirmed_789',
        'status' => 'succeeded',
        'card_brand' => 'mastercard',
        'card_last4' => '8888',
    ]);
});

test('advertiser can purchase custom duration slider boost plan with otp', function () {
    seedBoostOtp($this->user->id, '1234');

    $this->mock(StripeService::class, function (MockInterface $mock) {
        $mock->shouldReceive('getOrCreateCustomer')->once()->andReturn(
            Customer::constructFrom(['id' => 'cus_mock_slider']),
        );
        $mock->shouldReceive('attachPaymentMethod')->once()->andReturn(
            PaymentMethod::constructFrom(['id' => 'pm_custom_slider'])
        );
        $mock->shouldReceive('createPaymentIntent')->once()
            ->with(
                1500, // 5 days @ $3.00/day = $15.00 => 1500 cents
                'usd',
                'cus_mock_slider',
                'pm_custom_slider',
                Mockery::type('array'),
                true
            )
            ->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_slider_5d',
                'status' => 'succeeded',
                'amount' => 1500,
                'currency' => 'usd',
                'payment_method' => [
                    'card' => ['brand' => 'visa', 'last4' => '1111'],
                ],
            ]));
    });

    $response = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
            'checkout_type' => 'stripe',
            'custom_days' => 5,
            'payment_method' => 'pm_custom_slider',
            'otp' => '1234',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.campaign.duration_days', 5)
        ->assertJsonPath('data.campaign.amount_paid', '15.00');

    $this->assertDatabaseHas('ad_campaigns', [
        'boostable_id' => $this->product->id,
        'duration_days' => 5,
        'amount_paid' => 15.00,
        'status' => AdCampaignStatus::ACTIVE->value,
    ]);
});

test('mobile app can activate boost via revenuecat in-app purchase without email otp', function () {
    $response = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
            'checkout_type' => 'revenuecat',
            'boost_package_id' => $this->package->id,
            'revenuecat_transaction_id' => 'rc_store_trans_987654321',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.campaign.status', 'active')
        ->assertJsonPath('data.campaign.payment_platform', 'revenuecat')
        ->assertJsonPath('data.campaign.revenuecat_transaction_id', 'rc_store_trans_987654321')
        ->assertJsonPath('data.product.is_boosted', true);

    $this->assertDatabaseHas('ad_campaigns', [
        'boostable_id' => $this->product->id,
        'payment_platform' => 'revenuecat',
        'revenuecat_transaction_id' => 'rc_store_trans_987654321',
        'status' => AdCampaignStatus::ACTIVE->value,
        'amount_paid' => 19.99,
    ]);

    $this->assertDatabaseHas('transactions', [
        'provider_transaction_id' => 'rc_store_trans_987654321',
        'status' => 'succeeded',
        'amount' => 19.99,
    ]);

    // Test idempotency: Sending the same transaction id again should not create a duplicate
    $secondResponse = $this->actingAs($this->user, 'api')
        ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
            'checkout_type' => 'revenuecat',
            'boost_package_id' => $this->package->id,
            'revenuecat_transaction_id' => 'rc_store_trans_987654321',
        ]);

    $secondResponse->assertStatus(201);
    expect(AdCampaign::where('revenuecat_transaction_id', 'rc_store_trans_987654321')->count())->toBe(1);
});

test('advertiser can view product boost campaigns list and metrics', function () {
    AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $this->product->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->package->id,
        'ad_type' => 'product',
        'network_type' => 'neuroscience',
        'amount_paid' => 19.99,
        'currency' => 'USD',
        'duration_days' => 7,
        'starts_at' => now(),
        'ends_at' => now()->addDays(7),
        'status' => AdCampaignStatus::ACTIVE,
        'total_impressions' => 1500,
        'total_clicks' => 85,
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson("/api/industry/products/{$this->product->id}/boost/campaigns")
        ->assertStatus(200);

    $response->assertJsonStructure([
        'success',
        'data' => [
            'metrics' => [
                'total_campaigns',
                'active_campaigns',
                'total_impressions',
                'total_clicks',
                'total_spend',
                'current_is_boosted',
            ],
            'campaigns',
        ],
    ]);

    $response->assertJsonPath('data.metrics.total_campaigns', 1)
        ->assertJsonPath('data.metrics.active_campaigns', 1)
        ->assertJsonPath('data.metrics.total_impressions', 1500)
        ->assertJsonPath('data.metrics.total_clicks', 85)
        ->assertJsonPath('data.metrics.total_spend', 19.99);
});
