<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Role extends Model
{
    protected $guarded;

    const ROLES =[
        ['id' => 1, 'name' => 'Sales Representative'],
        ['id' => 2, 'name' => 'Sales Supervisor'],
        ['id' => 3, 'name' => 'Area Manager'],
        ['id' => 4, 'name' => 'Sales Manager'],
    ];

    public function commission() : HasOne
    {
        return $this->hasOne(RoleCommission::class,'role_id','id');
    }
}
