<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductNormalizationDecision extends BaseModel
{
    protected $table = 'product_normalization_decisions';

    protected $fillable = [
        'product_id',
        'raw_name',
        'normalized_raw_name',
        'selected_values',
        'decision_source',
        'algorithm_version',
        'reviewed_by',
    ];

    protected $casts = [
        'selected_values' => 'array',
        'algorithm_version' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
