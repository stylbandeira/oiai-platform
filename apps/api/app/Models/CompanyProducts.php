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
        'current_price',
        'price_per_base_unit',
    ];

    protected $casts = [
        'average_price' => 'float',
        'current_price' => 'float',
        'price_per_base_unit' => 'float',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function userAddedProducts(): HasMany
    {
        return $this->hasMany(UserAddedProducts::class, 'company_product_id');
    }

    public function effectivePrice(): float
    {
        return (float) ($this->current_price ?? $this->average_price ?? 0);
    }

    public function recalculatePricePerBaseUnit(): ?float
    {
        $this->loadMissing('product.unity');
        $baseQuantity = $this->product?->baseQuantity();
        $price = $this->effectivePrice();

        $this->price_per_base_unit = $baseQuantity && $price > 0
            ? $price / $baseQuantity
            : null;
        $this->saveQuietly();

        return $this->price_per_base_unit;
    }
}
