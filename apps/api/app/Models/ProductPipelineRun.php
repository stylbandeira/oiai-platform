<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductPipelineRun extends BaseModel
{
    protected $fillable = [
        'product_id',
        'operation',
        'status',
        'duration_ms',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
