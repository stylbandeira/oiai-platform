<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_category';
    protected $fillable = [
        'name'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn($value) => ucfirst($value),
            set: fn($value) => ucfirst($value)
        );
    }
}
