<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\IndustryJobPost;
use App\Models\User;
use App\Notifications\Concerns\BroadcastsJobNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class JobRejectedNotification extends Notification
{
    use BroadcastsJobNotification;

    public function __construct(
        public IndustryJobPost $job,
        public string $reason,
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
            'status' => 'rejected',
            'rejection_reason' => $this->reason,
            'rejected_by' => $this->admin?->id,
            'sender_name' => 'MindUnite Admin',
            'sender_username' => null,
            'sender_profile_image_url' => null,
            'action_url' => '/mu/recruiter-dashboard',
            'message' => "rejected your job posting \"{$this->job->job_title}\".",
            'description' => "Reason: {$this->reason}",
            'type' => class_basename(self::class),
            'rejected_at' => ($this->job->reviewed_at ?? now())->toIso8601String(),
            'unread_count' => $this->unreadNotificationCount($notifiable) + 1,
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title('Job Posting Rejected')
                    ->body("Your job \"{$this->job->job_title}\" was rejected. Reason: {$this->reason}")
            )
            ->data([
                'job_id' => (string) $this->job->id,
                'job_unique_id' => (string) ($this->job->job_id ?? ''),
                'industry_id' => (string) ($this->job->industry_id ?? ''),
                'status' => 'rejected',
                'rejection_reason' => $this->reason,
                'action_url' => '/mu/recruiter-dashboard',
                'type' => class_basename(self::class),
            ]);
    }
}

