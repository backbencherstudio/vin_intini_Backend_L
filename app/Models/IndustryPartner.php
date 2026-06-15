<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryPartner extends Model
{
    protected $fillable = ['network_type', 'industry_type', 'partner_name', 'partner_tag', 'partner_desc', 'partner_link'];

    protected $hidden = ['created_at', 'updated_at'];
}
