<?php

namespace App\Jobs;

use App\Models\LoginActivity;
use App\Notifications\NewLoginAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SendLoginAlertEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $activity;

    public function __construct(LoginActivity $activity)
    {
        $this->activity = $activity;
    }

    public function handle(): void
    {
        $activity = $this->activity;
        $user = $activity->user;

        if (! $user) {
            return;
        }

        $trustUrl = URL::temporarySignedRoute(
            'security.email-resolve',
            now()->addHours(24),
            ['id' => $activity->id, 'action' => 'trust']
        );

        $blockUrl = URL::temporarySignedRoute(
            'security.email-resolve',
            now()->addHours(24),
            ['id' => $activity->id, 'action' => 'block']
        );

        $user->notify(new NewLoginAlertNotification($activity, $trustUrl, $blockUrl));
    }
}
