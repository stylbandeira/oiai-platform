<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductDataProviderAttempt extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'provider',
        'status',
        'http_status',
        'message',
        'attempts',
        'last_attempt_at',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
