<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyProducts extends BaseModel
{
    use HasFactory;

    protected $table = 'company_products';

    protected $fillable = [
        'product_id',
        'company_id',
        'average_price',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function userAddedProducts(): HasMany
    {
        return $this->hasMany(UserAddedProducts::class, 'company_product_id');
    }
}
