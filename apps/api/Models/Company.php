<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Company extends BaseModel
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'INACTIVE';
    const STATUS_PENDING = 'pending';

    const VALID_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_PENDING
    ];

    protected $table = 'company';
    protected $fillable = [
        'address_id',
        'name',
        'cnpj',
        'img',
        'website',
        'email',
        'status',
        'phone',
        'description',
        'raw_address',
        'ie'
    ];
    protected $attributes = [
        'img' => './',
        'status' => 'active'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'company_products')
            ->withPivot(['average_price']);
    }

    public function owners()
    {
        return $this->belongsToMany(User::class, 'company_owners', 'company_id', 'user_id');
    }

    public function ownerRelationship()
    {
        return $this->hasOne(CompanyOwners::class, 'company_id', 'id');
    }

    public function getImgUrlAttribute()
    {
        if (!$this->img) {
            return null;
        }

        return Storage::url($this->img);
    }

    public function address()
    {
        return $this->hasOne(Address::class, 'id', 'address_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function scopeWithOwnerRelationshipFor($query, User $user)
    {
        return $query->with(['ownerRelationship' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }]);
    }

    public function scopeOwnedByActiveUser($query, User $user)
    {
        return $query->whereHas('owners', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->where('company_owners.status', CompanyOwners::STATUS_ACTIVE);
        });
    }
}
