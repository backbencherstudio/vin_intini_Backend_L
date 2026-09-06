<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmToken extends Model
{
    protected $table = 'fcm_tokens';

    protected $fillable = [
        'user_id',
        'fcm_token',
    ];

    public static function assignTo(User $user, string $fcmToken): void
    {
        static::where('fcm_token', $fcmToken)
            ->where('user_id', '!=', $user->id)
            ->delete();

        $user->fcmTokens()->updateOrCreate(
            ['fcm_token' => $fcmToken],
            ['user_id' => $user->id]
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
