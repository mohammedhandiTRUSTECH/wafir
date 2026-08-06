<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyRepTarget extends Model
{
    protected $fillable = [
        'erp_id',
        'rep_name',
        'year',
        'month',
        'target',
    ];

    protected $casts = [
        'erp_id' => 'integer',
        'year'   => 'integer',
        'month'  => 'integer',
        'target' => 'float',
    ];
}
