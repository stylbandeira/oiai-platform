<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SearchLog extends BaseModel
{
    protected $fillable = [
        'query', 'normalized_query', 'result_count', 'clicked_product_id', 'user_id',
    ];

    protected $casts = ['result_count' => 'integer'];

    public function clickedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'clicked_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
