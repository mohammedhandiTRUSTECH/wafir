<?php

namespace App\Models;

use Carbon\Traits\Timestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Translatable\HasTranslations;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $id
 * @property string $oid
 * @property string $WHSid
 * @property string $name
 * @property bool $is_active
 * @property int $role_id
 * @property string $password
 * @property Timestamp $created_at
 * @property Timestamp $updated_at
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $guarded;
    protected $with = ['userLocation'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'employee_id' => $this->employee_id,
            'oid' => $this->oid,
        ];
    }

    public function getOid(): string | null
    {
        return $this->oid;
    }

    public function target(): HasOne
    {
        return $this->hasOne(UserTarget::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id');

    }

    public function hasChildren(): bool
    {
        return (bool)$this->children()->count();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function userLocation(): HasOne
    {
        return $this->hasOne(UserLocation::class)->orderByDesc('id');
    }

    public function userLocations(): HasMany
    {
        return $this->hasMany(UserLocation::class)->orderByDesc('id');
    }

    public function userParents(): HasMany
    {
        return $this->hasMany(UserParent::class)->orderByDesc('id');
    }

    public function userTarget()
    {
        $target = 0;
        if ($this->role_id == 1) {
            if (isset($this->target)) {
                $target = $this->target->target->target;
            }
        } elseif ($this->role_id == 2) {
            $children = $this->children()->whereHas('target')->get()->pluck('id')->toArray();
            $targetObj = UserTarget::query()->with(['target'])->whereIn('user_id', $children)->get();
            foreach ($targetObj as $t) {
                $target = $t->target->target + $target;
            }
        } elseif ($this->role_id == 3) {
            $superVisors = $this->children()->get()->pluck('id')->toArray();
            $salesRep = User::query()->whereIn('parent_id', $superVisors)->get()->pluck('id')->toArray();
            $allChildren = array_merge($superVisors, $salesRep);
            $targetObj = UserTarget::query()
                ->whereHas('user',function ($query){
                    $query->where('role_id', 1);
                })
                ->with(['target'])->whereIn('user_id', $allChildren)->get();
            foreach ($targetObj as $t) {
                $target = $t->target->target + $target;
            }
        } elseif ($this->role_id == 4) {
            $areaManager = $this->children()->get()->pluck('id')->toArray();
            $superVisors = User::query()->whereIn('parent_id', $areaManager)->get()->pluck('id')->toArray();
            $salesRep = User::query()->whereIn('parent_id', $superVisors)->get()->pluck('id')->toArray();
            $allChildren = array_merge($areaManager, $superVisors, $salesRep);
            $targetObj = UserTarget::query()
                ->whereHas('user',function ($query){
                    $query->where('role_id', 1);
                })
                ->with(['target'])->whereIn('user_id', $allChildren)->get();
            foreach ($targetObj as $t) {
                $target = $t->target->target + $target;
            }
        }
        return $target;
    }
}
