<?php

namespace App\Jobs;

use App\Mail\NewLoginAlertMail;
use App\Models\LoginActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendLoginAlertEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $activity;

    public function __construct(LoginActivity $activity)
    {
        $this->activity = $activity;
    }

    public function handle()
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

        Mail::to($user->email)->send(new NewLoginAlertMail($activity, $trustUrl, $blockUrl));
    }
}
