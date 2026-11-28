<?php

namespace App\Jobs;

use App\Models\LoginActivity;
use App\Models\User;
use App\Services\Security\SecurityAuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class LogLoginJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected $userId,
        protected $ip,
        protected $userAgent,
        protected $tokenId = null,
        protected $status = 'Successful',
        protected $customDevice = null,
        protected $customPlatform = null,
        protected $deviceTokenHash = null,
    ) {}

    public function handle(): void
    {
        $agent = new Agent;
        $agent->setUserAgent($this->userAgent);

        $platform = $agent->platform();

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

        $loc = Location::get($this->ip);
        $locationName = $loc ? $loc->cityName.', '.$loc->countryName : 'Unknown';

        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $hasPriorSuccessfulLogin = LoginActivity::where('user_id', $this->userId)
            ->where('status', 'Successful')
            ->exists();

        $isFirstLogin = ! $hasPriorSuccessfulLogin;

        if (is_string($this->deviceTokenHash) && $this->deviceTokenHash !== '') {
            $isAlreadyTrusted = LoginActivity::where('user_id', $this->userId)
                ->where('status', 'Successful')
                ->where('device_token_hash', $this->deviceTokenHash)
                ->where('is_trusted', true)
                ->where('is_resolved', true)
                ->exists();
        } else {
            $isAlreadyTrusted = LoginActivity::where('user_id', $this->userId)
                ->where('status', 'Successful')
                ->whereNull('device_token_hash')
                ->where('device', $device)
                ->where('browser', $browser)
                ->where('location', $locationName)
                ->where('is_trusted', true)
                ->where('is_resolved', true)
                ->exists();
        }

        $shouldBeTrusted = ($isFirstLogin || $isAlreadyTrusted);

        // Deactivate previous active sessions for the exact same recognized device
        if ($this->status === 'Successful') {
            $sameDeviceQuery = LoginActivity::where('user_id', $this->userId)
                ->where('status', 'Successful')
                ->where('is_active', true);

            if (is_string($this->deviceTokenHash) && $this->deviceTokenHash !== '') {
                $sameDeviceQuery->where('device_token_hash', $this->deviceTokenHash);
            } else {
                $sameDeviceQuery->whereNull('device_token_hash')
                    ->where('device', $device)
                    ->where('browser', $browser);
            }

            $sameDeviceQuery->update(['is_active' => false]);
        }

        $activity = LoginActivity::create([
            'user_id' => $this->userId,
            'token_id' => $this->tokenId,
            'device_token_hash' => $this->deviceTokenHash,
            'device' => $device ?: 'Unknown Device',
            'browser' => $browser ?: 'Unknown Browser',
            'ip_address' => $this->ip,
            'location' => $locationName,
            'login_at' => now(),
            'status' => $this->status,
            'is_active' => ($this->status === 'Successful'),
            'is_trusted' => $shouldBeTrusted,
            'is_resolved' => $shouldBeTrusted,
        ]);

        app(SecurityAuditService::class)->log(
            $user,
            $this->status === 'Successful' ? 'login_success' : 'login_failed',
            [
                'device' => $activity->device,
                'browser' => $activity->browser,
                'location' => $locationName,
                'trusted' => $shouldBeTrusted,
            ],
            $this->status === 'Successful' ? ($shouldBeTrusted ? 'info' : 'warning') : 'warning',
        );

        if ($this->status === 'Successful' && ! $shouldBeTrusted) {
            SendLoginAlertEmailJob::dispatch($activity);
        }
    }
}
