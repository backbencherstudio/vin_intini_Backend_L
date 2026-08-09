<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Jobs\LogLoginJob;

class LogSuccessfulLogin
{
    public function handle(Login $event)
    {
        $tokenId = request('current_token_id') ?: (
            $event->guard === 'web' ? session()->getId() : null
        );

        LogLoginJob::dispatch(
            $event->user->id,
            request()->ip(),
            request()->userAgent(),
            $tokenId
        );
    }
}
