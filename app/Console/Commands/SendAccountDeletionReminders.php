<?php

namespace App\Console\Commands;

use App\Models\DeletedAccountLog;
use App\Models\User;
use App\Notifications\AccountDeletionReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAccountDeletionReminders extends Command
{
    protected $signature = 'account:send-deletion-reminders';

    protected $description = 'Send a reminder email 3 days before permanent account deletion';

    public function handle(): void
    {
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        $logs = DeletedAccountLog::whereDate('permanent_delete_at', $targetDate)->get();

        foreach ($logs as $log) {
            $user = User::withTrashed()->find($log->user_id);

            if ($user && $user->trashed()) {
                $user->notify(new AccountDeletionReminderNotification($user, $log->permanent_delete_at));
            }
        }
    }
}
