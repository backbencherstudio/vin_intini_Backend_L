<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\IndustryJobPost;
use App\Models\User;
use App\Notifications\Concerns\BroadcastsJobNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class AdminCreatedJobNotification extends Notification
{
    use BroadcastsJobNotification;

    public function __construct(
        public IndustryJobPost $job,
        public ?User $admin = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function notificationData(object $notifiable): array
    {
        $industry = $this->resolveIndustry();

        return [
            'job_id' => $this->job->id,
            'job_unique_id' => $this->job->job_id,
            'job_title' => $this->job->job_title,
            'industry_id' => $this->job->industry_id,
            'industry_name' => $industry?->name,
            'industry_slug' => $industry?->slug,
            'status' => (string) ($this->job->status->value ?? $this->job->status),
            'sender_name' => 'MindUnite Admin',
            'sender_username' => null,
            'sender_profile_image_url' => null,
            'action_url' => '/mu/recruiter-dashboard',
            'message' => "posted a new job listing \"{$this->job->job_title}\" on behalf of your company.",
            'description' => "Position: {$this->job->position} ({$this->job->work_mode})",
            'type' => class_basename(self::class),
            'created_at' => ($this->job->created_at ?? now())->toIso8601String(),
            'unread_count' => $this->unreadNotificationCount($notifiable) + 1,
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title('New Job Posted by Admin')
                    ->body("MindUnite Admin created a new job \"{$this->job->job_title}\" for your company.")
            )
            ->data([
                'job_id' => (string) $this->job->id,
                'job_unique_id' => (string) ($this->job->job_id ?? ''),
                'industry_id' => (string) ($this->job->industry_id ?? ''),
                'action_url' => '/mu/recruiter-dashboard',
                'type' => class_basename(self::class),
            ]);
    }
}

