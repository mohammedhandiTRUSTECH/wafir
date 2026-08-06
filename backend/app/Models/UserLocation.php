<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLocation extends Model
{
    protected $guarded;
    protected $with = ['location'];

    public function location() : BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
