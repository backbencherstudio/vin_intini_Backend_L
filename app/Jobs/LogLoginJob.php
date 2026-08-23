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

class LogLoginJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    protected $ip;

    protected $userAgent;

    protected $tokenId;

    protected $status;

    protected $customDevice;

    protected $customPlatform;

    public function __construct($userId, $ip, $userAgent, $tokenId = null, $status = 'Successful', $customDevice = null, $customPlatform = null)
    {
        $this->userId = $userId;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->tokenId = $tokenId;
        $this->status = $status;
        $this->customDevice = $customDevice;
        $this->customPlatform = $customPlatform;
    }

    public function handle()
    {
        $agent = new Agent;
        $agent->setUserAgent($this->userAgent);

        $platform = $agent->platform();
        $browser = $agent->browser();

        if ($this->customDevice) {
            $device = $this->customDevice.($this->customPlatform ? ' ('.$this->customPlatform.')' : '');
            $browser = 'Native Mobile App';
        } else {
            if ($agent->isPhone() || $agent->isTablet()) {
                $brand = $agent->device();
                $device = ($brand && $brand != 'WebKit') ? $brand.' ('.$platform.')' : $platform;
            } else {
                $device = $platform ?: 'Unknown Device';
            }
            $browser = $agent->browser() ?: 'Unknown Browser';
        }

        // if ($agent->isPhone() || $agent->isTablet()) {
        //     $brand = $agent->device();
        //     $device = ($brand && $brand != 'WebKit') ? $brand . ' (' . $platform . ')' : $platform;
        // } else {
        //     $device = $platform;
        // }

        $loc = Location::get($this->ip);
        $locationName = $loc ? $loc->cityName.', '.$loc->countryName : 'Unknown';

        $activity = LoginActivity::create([
            'user_id' => $this->userId,
            'token_id' => $this->tokenId,
            'device' => $device ?: 'Unknown Device',
            'browser' => $browser ?: 'Unknown Browser',
            'ip_address' => $this->ip,
            'location' => $locationName,
            'login_at' => now(),
            'status' => $this->status,
            'is_active' => ($this->status === 'Successful'),
            'is_trusted' => User::find($this->userId)?->created_at->gt(now()->subMinutes(5)) ? true : false,
        ]);

        // base on the status, send an email alert if it's a successful login
        if ($this->status === 'Successful') {
            $user = User::find($this->userId);

            if ($user && $user->created_at->lt(now()->subMinutes(5))) {
                $seenBefore = LoginActivity::where('user_id', $user->id)
                    ->where('status', 'Successful')
                    ->where('location', $locationName)
                    ->where('device', $device)
                    ->where('is_trusted', true)
                    ->exists();

                if (! $seenBefore) {
                    SendLoginAlertEmailJob::dispatch($activity);
                }
            }
        }
    }
}
