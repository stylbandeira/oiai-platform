<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyProducts extends BaseModel
{
    use HasFactory;
    protected $table = 'company_products';

    protected $fillable = [
        'product_id',
        'company_id',
        'average_price'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function userAddedProducts()
    {
        return $this->hasMany(UserAddedProducts::class, 'company_product_id');
    }
}
