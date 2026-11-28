<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Jobs\LogLoginJob;
use App\Services\Auth\DeviceTrustService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $tokenId = request('current_token_id') ?: (
            $event->guard === 'web' ? session()->getId() : null
        );

        $deviceHash = request()->attributes->get('resolved_device_hash')
            ?? app(DeviceTrustService::class)->extractDeviceHash(request());

        LogLoginJob::dispatchSync(
            $event->user->id,
            request()->ip(),
            request()->userAgent(),
            $tokenId,
            'Successful',
            request('device_name'),
            request('platform'),
            $deviceHash,
        );
    }
}
