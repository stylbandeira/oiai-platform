<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserAddedProducts extends BaseModel
{
    use HasFactory;

    protected $table = 'user_added_products';

    const AVERAGE_PRICE_JOB_CONSTANCY = 1;

    const AVERAGE_PRICE_JOB_CHUNK = 1000;

    protected $fillable = [
        'user_id',
        'company_id',
        'product_id',
        'price',
        'processed',
        'purchase_date',
    ];

    /** @return HasOne<Product, $this> */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    /** @return HasOne<Company, $this> */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /** @return BelongsTo<CompanyProducts, $this> */
    public function companyProducts(): BelongsTo
    {
        return $this->belongsTo(CompanyProducts::class, 'company_product_id');
    }
}
