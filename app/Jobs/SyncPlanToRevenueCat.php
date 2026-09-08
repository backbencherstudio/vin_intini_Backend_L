<?php

namespace App\Jobs;

use App\Models\Plan;
use App\Services\RevenueCatPlanSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncPlanToRevenueCat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 10;

    public function __construct(public Plan $plan) {}

    public function handle(RevenueCatPlanSyncService $sync): void
    {
        $sync->sync($this->plan);
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }
}
