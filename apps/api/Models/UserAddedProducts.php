<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'purchase_date'
    ];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function companyProducts()
    {
        return $this->belongsTo(CompanyProducts::class, 'company_product_id');
    }
}
