<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShoppingListRequirement extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'list_id', 'product_type_id', 'desired_quantity', 'desired_unit_id',
        'brand_id', 'variant_id', 'specific_product_id',
    ];

    protected $casts = ['desired_quantity' => 'float'];

    public function list(): BelongsTo
    {
        return $this->belongsTo(ItensList::class, 'list_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function desiredUnit(): BelongsTo
    {
        return $this->belongsTo(Unity::class, 'desired_unit_id');
    }

    public function specificProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'specific_product_id');
    }
}
