<?php

use App\Models\Conversation;
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

Schedule::command('model:prune')->daily();
