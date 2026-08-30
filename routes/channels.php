<?php

use App\Models\Conversation;
use App\Models\LoginActivity;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Schedule;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{id}', function ($user, $id) {
    return Conversation::where('id', $id)
        ->where(function ($q) use ($user) {
            $q->where('user_id_1', $user->id)->orWhere('user_id_2', $user->id);
        })
        ->exists();
});

// Schedule::command('model:prune')->daily();
Schedule::command('model:prune')->dailyAt('02:00')->timezone('America/New_York');

//added for sending account deletion reminder emails
Schedule::command('account:send-deletion-reminders')->dailyAt('10:00')->timezone('America/New_York');

//
Schedule::call(function () {
    LoginActivity::where('is_active', true)
        ->where('login_at', '<', now()->subDays(30))
        ->update(['is_active' => false]);
})->dailyAt('03:00')->timezone('America/New_York');
