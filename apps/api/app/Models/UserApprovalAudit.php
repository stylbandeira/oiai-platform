<?php

namespace App\Models;

class UserApprovalAudit extends BaseModel
{
    protected $fillable = [
        'target_user_id',
        'actor_user_id',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
