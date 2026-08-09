<?php

namespace App\Jobs;

use App\Models\LoginActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

// class LogLoginJob implements ShouldQueue
class LogLoginJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId, $ip, $userAgent, $tokenId;

    public function __construct($userId, $ip, $userAgent, $tokenId)
    {
        $this->userId = $userId;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->tokenId = $tokenId;
    }

    public function handle()
    {
        $agent = new Agent();
        $agent->setUserAgent($this->userAgent);

        $loc = Location::get($this->ip);
        $locationName = $loc ? $loc->cityName . ', ' . $loc->countryName : 'Unknown';

        LoginActivity::create([
            'user_id'    => $this->userId,
            'device'     => $agent->platform(), // e.g., Windows, Mac
            'browser'    => $agent->browser(),  // e.g., Chrome, Firefox
            'ip_address' => $this->ip,
            'location'   => $locationName,
            'login_at'   => now(),
            'status'     => 'Successful',
            'token_id'   => $this->tokenId,
            'is_active'  => true,
        ]);
    }
}
