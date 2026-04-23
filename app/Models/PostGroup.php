<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostGroup extends Model
{
    protected $fillable = [
        'post_id',
        'group_id',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    
}
