<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedAccountLog extends Model
{
    protected $fillable = ['user_id', 'user_name', 'user_email', 'reason', 'requested_at', 'permanent_delete_at'];
}
