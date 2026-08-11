<?php

namespace App\Jobs;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;
use App\Jobs\SendLoginAlertEmailJob;

class LogLoginJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId, $ip, $userAgent, $tokenId, $status;

    public function __construct($userId, $ip, $userAgent, $tokenId = null, $status = 'Successful')
    {
        $this->userId = $userId;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->tokenId = $tokenId;
        $this->status = $status;
    }

    public function handle()
    {
        $agent = new Agent();
        $agent->setUserAgent($this->userAgent);

        $platform = $agent->platform();
        $browser = $agent->browser();

        if ($agent->isPhone() || $agent->isTablet()) {
            $brand = $agent->device();
            $device = ($brand && $brand != 'WebKit') ? $brand . ' (' . $platform . ')' : $platform;
        } else {
            $device = $platform;
        }

        $loc = Location::get($this->ip);
        $locationName = $loc ? $loc->cityName . ', ' . $loc->countryName : 'Unknown';

        $activity = LoginActivity::create([
            'user_id'    => $this->userId,
            'token_id'   => $this->tokenId,
            'device'     => $device ?: 'Unknown Device',
            'browser'    => $browser ?: 'Unknown Browser',
            'ip_address' => $this->ip,
            'location'   => $locationName,
            'login_at'   => now(),
            'status'     => $this->status,
            'is_active'  => ($this->status === 'Successful'),
        ]);

        // base on the status, send an email alert if it's a successful login
        if ($this->status === 'Successful') {
            $user = User::find($this->userId);

            if ($user) {
                $seenBefore = LoginActivity::where('user_id', $user->id)
                    ->where('status', 'Successful')
                    ->where('location', $locationName)
                    ->where('device', $device)
                    ->where('is_resolved', true)
                    ->exists();

                if (!$seenBefore) {
                    SendLoginAlertEmailJob::dispatch($activity);
                }
            }
        }
    }
}
