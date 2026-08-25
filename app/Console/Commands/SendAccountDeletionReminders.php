<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeletedAccountLog;
use App\Models\User;
use App\Mail\AccountDeletionReminderMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendAccountDeletionReminders extends Command
{
    protected $signature = 'account:send-deletion-reminders';
    protected $description = 'Send a reminder email 3 days before permanent account deletion';

    public function handle()
    {
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        $logs = DeletedAccountLog::whereDate('permanent_delete_at', $targetDate)->get();

        foreach ($logs as $log) {
            $user = User::withTrashed()->find($log->user_id);

            if ($user && $user->trashed()) {
                Mail::to($user->email)->queue(new AccountDeletionReminderMail($user, $log->permanent_delete_at));
            }
        }
    }
}
