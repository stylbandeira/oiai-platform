<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListProducts extends BaseModel
{
    use HasFactory;

    protected $table = 'list_products';

    protected $attributes = [
        'company_product_id' => 0,
    ];

    public $fillable = [
        'list_id',
        'product_id',
        'quantity',
    ];

    /** @return BelongsTo<ItensList, $this> */
    public function list(): BelongsTo
    {
        return $this->belongsTo(ItensList::class, 'list_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** @return BelongsTo<CompanyProducts, $this> */
    public function companyProduct(): BelongsTo
    {
        return $this->belongsTo(CompanyProducts::class, 'company_product_id');
    }
}
