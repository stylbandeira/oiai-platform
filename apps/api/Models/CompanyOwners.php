<?php

namespace App\Models;

class CompanyOwners extends BaseModel
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';

    const VALID_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_PENDING,
    ];

    protected $table = 'company_owners';
    protected $fillable = [
        'user_id',
        'company_id',
        'status',
        'message',
        'approved_at',
        'approved_by',
    ];
}
