<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\LogLoginJob;

class LogFailedLogin
{
    public function handle(Failed $event)
    {
        if ($event->user) {
            $ip = request()->ip();
            $userAgent = request()->userAgent();

            if (!$userAgent) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            }

            LogLoginJob::dispatch(
                $event->user->id,
                $ip,
                $userAgent,
                null,
                'Failed',
                request('device_name'),
                request('platform')
            );
        }
    }
}
