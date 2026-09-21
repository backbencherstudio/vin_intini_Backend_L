<?php

namespace App\Jobs;

use App\Models\WebhookLog;
use App\Services\RevenueCatWebhookService;
use App\Services\WebhookLoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessRevenueCatWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $backoff = 10;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
        public ?int $webhookLogId = null,
    ) {}

    public function handle(RevenueCatWebhookService $webhook, WebhookLoggerService $logger): void
    {
        $log = $this->webhookLogId ? WebhookLog::find($this->webhookLogId) : null;

        try {
            $webhook->handle($this->payload);
            $logger->markProcessed($log);
        } catch (Throwable $exception) {
            $logger->markFailed($log, $exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
