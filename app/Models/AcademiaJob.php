<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademiaJob extends Model
{
    protected $table = 'academia_jobs';
    protected $fillable = ['state_id', 'title', 'company_name', 'location', 'salary_min', 'salary_max', 'category'];
}
