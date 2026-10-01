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

class JobApplicationReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public IndustryJobApplication $application,
        public IndustryJobPost $jobPost,
        public User $applicant
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

        $applicantName = trim(
            ($this->applicant->first_name ?? '') . ' ' . ($this->applicant->last_name ?? '')
        ) ?: $this->application->full_name;

        return [
            // Company & Creator Identifiers (Future-proof)
            'industry_id'              => $this->jobPost->industry_id,
            'creator_id'               => $this->jobPost->created_by,

            // Job & Application details
            'job_id'                   => $this->jobPost->id,
            'job_unique_id'            => $this->jobPost->job_id,
            'job_title'                => $this->jobPost->job_title,
            'application_id'           => $this->application->id,
            'application_unique_id'    => $this->application->application_id,

            // Applicant details
            'applicant_id'             => $this->applicant->id,
            'applicant_username'       => $this->applicant->username,
            'applicant_name'           => $applicantName,
            'applicant_profile_image'  => $this->applicant->profile_image,
            'applicant_profile_url'    => $this->applicant->profile_image_url,

            // Notification meta
            'message'                  => "applied for your job position: {$this->jobPost->job_title}",
            'type'                     => class_basename(self::class),
            'applied_at'               => $this->application->created_at?->toIso8601String(),
            'unread_count'             => $unreadCount + 1,
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

    /**
     * User channel ebong Industry channel dutotey broadcast kora hocche
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // 1. Current Job Creator User Channel
        if ($this->jobPost->created_by) {
            $channels[] = new PrivateChannel('App.Models.User.' . $this->jobPost->created_by);
        }

        // 2. Company / Industry Channel (Future-proof: jekhane company er jekono admin listen korte parbe)
        // if ($this->jobPost->industry_id) {
        //     $channels[] = new PrivateChannel('App.Models.Industry.' . $this->jobPost->industry_id);
        // }

        return $channels;
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $applicantName = trim(
            ($this->applicant->first_name ?? '') . ' ' . ($this->applicant->last_name ?? '')
        ) ?: $this->application->full_name;

        return FcmMessage::create()
            ->notification(
                FcmNotification::create()
                    ->title($applicantName)
                    ->body("applied for your job: {$this->jobPost->job_title}")
            )
            ->data([
                'industry_id'           => (string) $this->jobPost->industry_id,
                'creator_id'            => (string) $this->jobPost->created_by,
                'job_id'                => (string) $this->jobPost->id,
                'application_id'        => (string) $this->application->id,
                'application_unique_id' => (string) $this->application->application_id,
                'applicant_id'          => (string) $this->applicant->id,
                'type'                  => class_basename(self::class),
                'applied_at'            => $this->application->created_at?->toIso8601String(),
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
