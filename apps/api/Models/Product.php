<?php

namespace App\Models;

use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends BaseModel
{
    use HasFactory, SoftDeletes;
    protected $table = 'products';
    const AVERAGE_PRICE_JOB_CONSTANCY_DAYS = 1;
    const AVERAGE_PRICE_PURCHASE_DATE_LIMIT_WEEKS = 4;

    protected $fillable = [
        'unit_id',
        'quantity',
        'name',
        'normalized_quantity',
        'quantity_dimension',
        'quantity_source',
        'quantity_confidence',
        'raw_name',
        'normalized_name',
        'search_description',
        'img',
        'sku',
        'average_price',
        'category_id',
        'ean',
        'ncm',
        'description',
        'validated',
        'validated_by',
        'created_by',
    ];

    protected $attributes = [
        'category_id' => 1,
        'listAdded' => 0,
        'description' => '',
        'average_price' => NULL
    ];

    protected $casts = [
        'average_price' => 'float',
        'quantity_confidence' => 'float',
        'quantity_source' => ProductQuantitySource::class,
        'refined' => ProductRefinementStatus::class,
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_products')
            ->withPivot(['average_price']);
    }

    public function userAddedProducts()
    {
        return $this->hasMany(UserAddedProducts::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unity()
    {
        return $this->belongsTo(Unity::class, 'unit_id');
    }

    public function providerAttempts()
    {
        return $this->hasMany(ProductDataProviderAttempt::class);
    }

    public function userFavorites()
    {
        return $this->belongsToMany(User::class, 'favorite_products', 'product_id', 'user_id');
    }

    public function getMentionedQuantityVariantAttribute()
    {
        if ($this->mentioned_quantity > 100) {
            return 'perfect';
        } else if ($this->mentioned_quantity > 50) {
            return 'secondary';
        } else {
            return 'destructive';
        }
    }
}
