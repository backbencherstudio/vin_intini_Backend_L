<?php

namespace App\Services;

use App\Models\WebhookLog;
use Throwable;

class WebhookLoggerService
{
    /**
     * Log an incoming webhook payload into the database if enabled.
     *
     * @param  array<string, mixed>|string  $payload
     */
    public function logReceived(
        string $provider,
        ?string $eventType = null,
        ?string $eventId = null,
        array|string $payload = []
    ): ?WebhookLog {
        if (! config('services.webhooks.log_payloads', true)) {
            return null;
        }

        $payloadData = is_string($payload) ? json_decode($payload, true) ?? ['raw' => $payload] : $payload;

        return WebhookLog::create([
            'provider' => $provider,
            'event_type' => $eventType,
            'event_id' => $eventId,
            'payload' => $payloadData,
            'status' => 'pending',
        ]);
    }

    /**
     * Mark a webhook log as successfully processed.
     */
    public function markProcessed(?WebhookLog $log): void
    {
        $log?->update([
            'status' => 'processed',
            'error_message' => null,
        ]);
    }

    /**
     * Mark a webhook log as failed with error details.
     */
    public function markFailed(?WebhookLog $log, Throwable|string $error): void
    {
        if (! $log) {
            return;
        }

        $message = is_string($error) ? $error : ($error->getMessage()."\n".$error->getTraceAsString());

        $log->update([
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }
}
