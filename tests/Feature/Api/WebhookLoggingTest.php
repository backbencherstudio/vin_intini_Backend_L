<?php

namespace Tests\Feature\Api;

use App\Models\WebhookLog;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Stripe\Event;
use Tests\TestCase;

class WebhookLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['revenuecat.webhook_secret' => 'test_secret']);
        config(['queue.default' => 'sync']);
    }

    public function test_revenuecat_webhook_creates_log_in_database(): void
    {
        $payload = [
            'event' => [
                'id' => 'evt_rc_123',
                'type' => 'INITIAL_PURCHASE',
                'app_user_id' => 'user_123',
                'product_id' => 'product_abc',
            ],
        ];

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'test_secret');

        $response = $this->call(
            'POST',
            '/api/webhooks/revenuecat',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_REVENUECAT_WEBHOOK_SIGNATURE' => $signature],
            $body
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'revenuecat',
            'event_type' => 'INITIAL_PURCHASE',
            'event_id' => 'evt_rc_123',
            'status' => 'processed',
        ]);

        $log = WebhookLog::where('event_id', 'evt_rc_123')->first();
        $this->assertNotNull($log);
        $this->assertEquals('user_123', $log->payload['event']['app_user_id'] ?? null);
    }

    public function test_stripe_webhook_creates_log_in_database(): void
    {
        $payload = json_encode([
            'id' => 'evt_stripe_456',
            'type' => 'customer.subscription.updated',
            'data' => ['object' => ['id' => 'sub_123']],
        ]);

        $event = Event::constructFrom(json_decode($payload, true));

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event): void {
            $mock->shouldReceive('constructEvent')
                ->once()
                ->andReturn($event);
        });

        $response = $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 'sig_test'],
            $payload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'stripe',
            'event_type' => 'customer.subscription.updated',
            'event_id' => 'evt_stripe_456',
            'status' => 'processed',
        ]);
    }

    public function test_webhook_logging_can_be_disabled_via_config(): void
    {
        config(['services.webhooks.log_payloads' => false]);

        $payload = [
            'event' => [
                'id' => 'evt_disabled_1',
                'type' => 'RENEWAL',
            ],
        ];

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'test_secret');

        $this->call(
            'POST',
            '/api/webhooks/revenuecat',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_REVENUECAT_WEBHOOK_SIGNATURE' => $signature],
            $body
        );

        $this->assertDatabaseMissing('webhook_logs', [
            'event_id' => 'evt_disabled_1',
        ]);
    }

    public function test_model_prunable_query_identifies_expired_logs(): void
    {
        config(['services.webhooks.retention_days' => 30]);

        $oldLog = WebhookLog::create([
            'provider' => 'stripe',
            'event_type' => 'old_event',
            'status' => 'processed',
        ]);
        WebhookLog::where('id', $oldLog->id)->update(['created_at' => now()->subDays(31)]);

        $newLog = WebhookLog::create([
            'provider' => 'stripe',
            'event_type' => 'new_event',
            'status' => 'processed',
        ]);
        WebhookLog::where('id', $newLog->id)->update(['created_at' => now()->subDays(10)]);

        $prunableLogs = (new WebhookLog)->prunable()->get();

        $this->assertCount(1, $prunableLogs);
        $this->assertEquals('old_event', $prunableLogs->first()->event_type);
    }
}
