<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BillingWebhookService;
use App\Services\StripeService;
use App\Services\WebhookLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeService $stripe,
        private BillingWebhookService $billing,
        private WebhookLoggerService $logger,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = $this->stripe->constructEvent($payload, (string) $signature);
        } catch (SignatureVerificationException $e) {
            $log = $this->logger->logReceived('stripe', null, null, $payload);
            $this->logger->markFailed($log, 'Invalid signature: '.$e->getMessage());

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            $log = $this->logger->logReceived('stripe', null, null, $payload);
            $this->logger->markFailed($log, 'Invalid payload: '.$e->getMessage());

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $log = $this->logger->logReceived(
            provider: 'stripe',
            eventType: $event->type,
            eventId: $event->id,
            payload: $payload,
        );

        try {
            match ($event->type) {
                'checkout.session.completed' => $this->billing->handleCheckoutCompleted($event->data->object),
                'customer.subscription.updated' => $this->billing->handleSubscriptionUpdated($event->data->object),
                'customer.subscription.deleted' => $this->billing->handleSubscriptionDeleted($event->data->object),
                'payment_intent.payment_failed' => $this->billing->handlePaymentIntentFailed($event->data->object),
                'charge.refunded' => $this->billing->handleChargeRefunded($event->data->object),
                'invoice.paid' => $this->billing->handleInvoicePaid($event->data->object),
                'invoice.payment_failed' => $this->billing->handleInvoicePaymentFailed($event->data->object),
                default => null,
            };

            $this->logger->markProcessed($log);
        } catch (\Throwable $e) {
            $this->logger->markFailed($log, $e);

            throw $e;
        }

        return response()->json(['received' => true], 200);
    }
}
