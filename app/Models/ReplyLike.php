<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplyLike extends Model
{
    protected $fillable = [
        'reply_id',
        'user_id',
    ];

    public function reply()
    {
        return $this->belongsTo(Reply::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
