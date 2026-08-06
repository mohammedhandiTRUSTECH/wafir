<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyWorkingDays extends Model
{
    protected $table = 'monthly_working_days';

    protected $fillable = [
        'year',
        'month',
        'working_days',
    ];

    protected $casts = [
        'year'          => 'integer',
        'month'         => 'integer',
        'working_days'  => 'integer',
    ];
}
