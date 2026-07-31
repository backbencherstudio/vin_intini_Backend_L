<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BillingWebhookService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeService $stripe,
        private BillingWebhookService $billing,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = $this->stripe->constructEvent($payload, (string) $signature);
        } catch (SignatureVerificationException) {
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

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

        return response()->json(['received' => true], 200);
    }
}
