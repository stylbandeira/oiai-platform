<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends BaseModel
{
    const TYPE_PRODUCT_INSERT = 'product_insert';
    const TYPE_COMPANY_OWNER_REQUEST = 'company_ownership_request';
    const TYPE_COMPANY_OWNER_ALLOWED = 'company_ownership_allowed';

    use HasFactory;
    protected $table = 'event';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'where',
        'type',
        'points',
        'link',
        'checked',
        'target_type',
        'entity_type',
        'entity_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
