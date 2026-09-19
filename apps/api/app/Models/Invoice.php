<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends BaseModel
{
    use HasFactory;

    const VALID_AREA_CODES = [
        'PE' => '26',
        'SP' => '35',
    ];

    protected $table = 'invoice';

    public $fillable = [
        'access_key',
        'user_id',
        'company_id',
        'receipt_data',
        'invoice_data',
        'pending',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
