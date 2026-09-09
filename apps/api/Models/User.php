<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
// implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    const POINTS = 'points';
    const VALID_STATUSES = [
        'active',
        'inactive',
        'suspended'
    ];
    const VALID_TYPES = [
        'admin',
        'client',
        'company'
    ];

    const TYPE_ADMIN = 'admin';
    const TYPE_CLIENT = 'client';
    const TYPE_COMPANY = 'company';

    const ALLOWED_ACTIVITY_TYPE = [
        Event::TYPE_PRODUCT_INSERT
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'email',
        'password',
        'cpf',
        'status',
        'points',
        'hasNotification'
    ];

    protected $attributes = [
        'cpf' => null,
        'must_change_password' => true,
        'type' => 'client',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_confirmation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_owners', 'user_id', 'company_id')
            ->withPivot(['status', 'message', 'approved_at', 'approved_by']);
    }

    public function activeCompanies()
    {
        return $this->belongsToMany(Company::class, 'company_owners', 'user_id', 'company_id')
            ->withPivot(['status', 'message', 'approved_at', 'approved_by'])
            ->wherePivot('status', 'active');
    }

    public function ownsActiveCompany(Company $company): bool
    {
        return $this->activeCompanies()
            ->whereKey($company->id)
            ->exists();
    }

    public function pendingCompanies()
    {
        return $this->belongsToMany(Company::class, 'company_owners', 'user_id', 'company_id')
            ->withPivot(['status', 'message', 'approved_at', 'approved_by'])
            ->wherePivot('status', 'pending');
    }


    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'favorite_products', 'user_id', 'product_id');
    }

    public function lists()
    {
        return $this->hasOne(ItensList::class, 'user_id');
    }

    public function activeLists()
    {
        return $this->lists()->where('status', 'active');
    }

    /**
     * TODO EPIC 015
     *
     * @return void
     */
    public function getMonthEconomyAttribute()
    {
        return 0;
    }

    public function recentActivity()
    {
        return $this->hasMany(Event::class)->whereIn('title', $this::ALLOWED_ACTIVITY_TYPE)->orderBy('created_at', 'DESC')->take(5);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function visibleEvents()
    {
        $companiesIds = collect($this->companies)->pluck('id');

        return Event::query()
            ->where(function ($query) use ($companiesIds) {

                if ($this->isClient()) {
                    $query->where('user_id', $this->id)
                        ->where('checked', false);
                }

                if ($this->isAdmin()) {
                    $query->orWhere('target_type', 'admin');
                }

                if ($this->isCompany()) {
                    $query->orWhere(function ($query) use ($companiesIds) {
                        $query->where('target_type', 'company')
                            ->where('entity_type', 'company')
                            ->whereIn('entity_id', $companiesIds)

                            ->orWhere('target_type', 'company')
                            ->where('entity_type', 'user')
                            ->where('entity_id', $this->id);
                    })->where('checked', false);
                }

                $query->orWhere('target_type', 'all');
            });
    }

    public function notifications()
    {
        return $this->visibleEvents()
            ->where('checked', false);
    }

    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    public function isCompany(): bool
    {
        return $this->type === self::TYPE_COMPANY;
    }

    public function isClient(): bool
    {
        return $this->type === self::TYPE_CLIENT;
    }

    public function hasAccessToCompany(int $companyId)
    {
        return $this->activeCompanies()
            ->whereKey($companyId)
            ->exists();
    }
}
