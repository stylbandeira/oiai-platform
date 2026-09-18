<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<Product, $this> */
    public function product(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<Company, $this> */
    public function company(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<User, $this> */
    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /** @return BelongsTo<CompanyProducts, $this> */
    public function companyProducts(): BelongsTo
    {
        return $this->belongsTo(CompanyProducts::class, 'company_product_id');
    }
}
