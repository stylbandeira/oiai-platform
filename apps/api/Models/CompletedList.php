<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompletedList extends BaseModel
{
    protected $fillable = ['list_id', 'list_data', 'version', 'total_price', 'completed_at'];

    protected function casts(): array
    {
        return [
            'list_data' => 'array',
            'total_price' => 'float',
            'completed_at' => 'datetime',
        ];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(ItensList::class, 'list_id');
    }
}
