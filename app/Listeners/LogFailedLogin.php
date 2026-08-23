<?php

namespace App\Listeners;

use App\Jobs\LogLoginJob;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event)
    {
        if ($event->user) {
            $ip = request()->ip();
            $userAgent = request()->userAgent();

            if (! $userAgent) {
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
