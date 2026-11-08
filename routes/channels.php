<?php

use App\Models\Conversation;
use App\Models\Industry;
use App\Models\IndustryConversation;
use App\Models\LoginActivity;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Schedule;

Broadcast::channel('App.Models.User.{id}', fn ($user, $id): bool => (int) $user->id === (int) $id);

Broadcast::channel('conversation.{id}', fn ($user, $id) => Conversation::where('id', $id)
    ->where(function ($q) use ($user): void {
        $q->where('user_id_1', $user->id)->orWhere('user_id_2', $user->id);
    })
    ->exists());

Broadcast::channel('industry-conversation.{id}', function ($user, $id): bool {
    $conversation = IndustryConversation::with('industry')->find($id);
    if (! $conversation) {
        return false;
    }

    return (int) $conversation->user_id === (int) $user->id
        || ((int) $conversation->industry?->created_by === (int) $user->id);
});

Broadcast::channel('industry.{id}', fn ($user, $id) => Industry::where('id', $id)->where('created_by', $user->id)->exists());

// Schedule::command('model:prune')->daily();
Schedule::command('model:prune')->dailyAt('02:00')->timezone('America/New_York');

// added for sending account deletion reminder emails
Schedule::command('account:send-deletion-reminders')->dailyAt('10:00')->timezone('America/New_York');

// session cleanup for login activities older than 30 days and not from native mobile app
Schedule::call(function (): void {
    $rollingMinutes = config('jwt.rolling_window', 43200);
    LoginActivity::where('is_active', true)
        ->where('updated_at', '<', now()->subMinutes($rollingMinutes))
        ->update(['is_active' => false]);
})->dailyAt('03:00')->timezone('America/New_York');
