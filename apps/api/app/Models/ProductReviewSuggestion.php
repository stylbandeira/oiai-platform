<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReviewSuggestion extends BaseModel
{
    protected $fillable = [
        'product_id', 'suggested_product_id', 'submitted_by', 'type',
        'suggested_values', 'original_name', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'suggested_values' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function suggestedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'suggested_product_id');
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
