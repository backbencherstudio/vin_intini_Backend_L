<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\IndustryJobPost;
use App\Models\User;
use App\Notifications\Concerns\BroadcastsJobNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class JobStatusUpdatedNotification extends Notification
{
    use BroadcastsJobNotification;

    public function __construct(
        public IndustryJobPost $job,
        public string $previousStatus,
        public string $newStatus,
        public ?User $admin = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function notificationData(object $notifiable): array
    {
        $industry = $this->resolveIndustry();

        $actionText = match ($this->newStatus) {
            'published' => "approved and published your job posting \"{$this->job->job_title}\".",
            'rejected' => "rejected your job posting \"{$this->job->job_title}\".",
            default => "updated the status of your job \"{$this->job->job_title}\" to {$this->newStatus}.",
        };

        return [
            'job_id' => $this->job->id,
            'job_unique_id' => $this->job->job_id,
            'job_title' => $this->job->job_title,
            'industry_id' => $this->job->industry_id,
            'industry_name' => $industry?->name,
            'industry_slug' => $industry?->slug,
            'status' => $this->newStatus,
            'previous_status' => $this->previousStatus,
            'sender_name' => 'MindUnite Admin',
            'sender_username' => null,
            'sender_profile_image_url' => null,
            'action_url' => '/mu/recruiter-dashboard',
            'message' => $actionText,
            'description' => "Status changed from {$this->previousStatus} to {$this->newStatus}",
            'type' => class_basename(self::class),
            'updated_at' => now()->toIso8601String(),
            'unread_count' => $this->unreadNotificationCount($notifiable) + 1,
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title('Job Status Updated')
                    ->body("The status of your job \"{$this->job->job_title}\" was updated to {$this->newStatus}.")
            )
            ->data([
                'job_id' => (string) $this->job->id,
                'job_unique_id' => (string) ($this->job->job_id ?? ''),
                'industry_id' => (string) ($this->job->industry_id ?? ''),
                'status' => $this->newStatus,
                'action_url' => '/mu/recruiter-dashboard',
                'type' => class_basename(self::class),
            ]);
    }
}

