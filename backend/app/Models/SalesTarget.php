<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTarget extends Model
{
    use SoftDeletes;

    protected $guarded;

    public function details(): HasMany
    {
        return $this->hasMany(SaleTargetDetails::class);
    }

    public function users() : HasMany
    {
        return $this->hasMany(UserTarget::class);
    }
}
