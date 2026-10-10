<?php

namespace App\Notifications;

use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class JobApplicationStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public IndustryJobApplication $application,
        public IndustryJobPost $jobPost,
        public string $newStatus,
        public ?User $updater = null
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->hasValidPusherBroadcastConfig()) {
            $channels[] = 'broadcast';
        }

        if (method_exists($notifiable, 'routeNotificationForFcm') && $notifiable->routeNotificationForFcm($this) !== []) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    private function notificationData(object $notifiable): array
    {
        $unreadCount = method_exists($notifiable, 'unreadNotifications')
            ? $notifiable->unreadNotifications()->count()
            : 0;

        $industry = $this->jobPost->relationLoaded('industry')
            ? $this->jobPost->industry
            : $this->jobPost->industry()->first();

        $companyName = $industry?->name ?? 'Company';
        $formattedStatus = ucfirst($this->newStatus);

        // Industry Logo URL
        $rawLogo = $industry?->logo;
        $industryLogoUrl = null;
        if ($rawLogo) {
            $industryLogoUrl = str_starts_with($rawLogo, 'http')
                ? $rawLogo
                : asset('storage/'.ltrim($rawLogo, '/'));
        }

        return [
            // Company & Post identifiers
            'industry_id' => $this->jobPost->industry_id,
            'industry_name' => $companyName,
            'industry_logo' => $rawLogo,
            'industry_logo_url' => $industryLogoUrl, // Full image URL
            'job_id' => $this->jobPost->id,
            'job_unique_id' => $this->jobPost->job_id,
            'job_title' => $this->jobPost->job_title,

            // Application & Status details
            'application_id' => $this->application->id,
            'application_unique_id' => $this->application->application_id,
            'status' => $this->newStatus,

            // Sender/Updater info
            'updater_id' => $this->updater?->id,
            'updater_name' => $this->updater ? trim(($this->updater->first_name ?? '').' '.($this->updater->last_name ?? '')) : null,

            // Notification Meta
            'message' => "Your application for {$this->jobPost->job_title} has been updated to {$formattedStatus}",
            'type' => class_basename(self::class),
            'updated_at' => now()->toIso8601String(),
            'unread_count' => $unreadCount + 1,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->notificationData($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage(
            $this->notificationData($notifiable)
        );
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->application->applicant_id),
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $industry = $this->jobPost->relationLoaded('industry')
            ? $this->jobPost->industry
            : $this->jobPost->industry()->first();
        $companyName = $industry?->name ?? 'Company';
        $formattedStatus = ucfirst($this->newStatus);

        $rawLogo = $industry?->logo;
        $industryLogoUrl = null;
        if ($rawLogo) {
            $industryLogoUrl = str_starts_with($rawLogo, 'http')
                ? $rawLogo
                : asset('storage/'.ltrim($rawLogo, '/'));
        }

        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title("Application Status: {$formattedStatus}")
                    ->body("Your application for {$this->jobPost->job_title} at {$companyName} is now {$formattedStatus}.")
            )
            ->data([
                'industry_id' => (string) $this->jobPost->industry_id,
                'industry_logo_url' => (string) ($industryLogoUrl ?? ''),
                'job_id' => (string) $this->jobPost->id,
                'application_id' => (string) $this->application->id,
                'application_unique_id' => (string) $this->application->application_id,
                'status' => (string) $this->newStatus,
                'type' => class_basename(self::class),
                'updated_at' => now()->toIso8601String(),
            ]);
    }

    private function hasValidPusherBroadcastConfig(): bool
    {
        return config('broadcasting.default') === 'pusher'
            && filled(config('broadcasting.connections.pusher.app_id'))
            && filled(config('broadcasting.connections.pusher.key'))
            && filled(config('broadcasting.connections.pusher.secret'));
    }
}
