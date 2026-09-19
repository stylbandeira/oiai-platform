<?php

namespace App\Models;

use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property int $registrations
 */
class Product extends BaseModel
{
    use HasFactory, Searchable, SoftDeletes;

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
        'average_price' => null,
    ];

    protected $casts = [
        'average_price' => 'float',
        'quantity_confidence' => 'float',
        'quantity_source' => ProductQuantitySource::class,
        'refined' => ProductRefinementStatus::class,
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_products')
            ->withPivot(['average_price']);
    }

    public function userAddedProducts(): HasMany
    {
        return $this->hasMany(UserAddedProducts::class, 'product_id');
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /** @return BelongsTo<Unity, $this> */
    public function unity(): BelongsTo
    {
        return $this->belongsTo(Unity::class, 'unit_id');
    }

    public function providerAttempts(): HasMany
    {
        return $this->hasMany(ProductDataProviderAttempt::class);
    }

    public function userFavorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_products', 'product_id', 'user_id');
    }

    public function searchableAs(): string
    {
        return config('scout.prefix').'products';
    }

    public function toSearchableArray(): array
    {
        $attributes = $this->attributesToArray();

        return array_filter([
            'id' => $this->getKey(),
            'name' => $this->name,
            'normalized_name' => $this->normalized_name,
            'description' => $this->search_description ?: ($this->description ?: null),
            'brand' => $attributes['brand'] ?? null,
            'category' => $this->category?->name,
            'aliases' => $attributes['aliases'] ?? [],
            'search_terms' => $attributes['search_terms'] ?? null,
            'ean' => $this->ean,
            'sku' => $this->sku,
            'quantity_base' => $this->normalized_quantity,
            'quantity_dimension' => $this->quantity_dimension,
            'validated' => (bool) $this->validated,
            'average_price' => $this->average_price,
            'brand_id' => $attributes['brand_id'] ?? null,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'available_states' => $attributes['available_states'] ?? [],
            'popularity' => $attributes['popularity'] ?? 0,
            'offer_count' => $attributes['offer_count'] ?? 0,
            'updated_at' => $this->updated_at?->timestamp,
        ], static fn ($value) => $value !== null);
    }

    public function getMentionedQuantityVariantAttribute()
    {
        if ($this->mentioned_quantity > 100) {
            return 'perfect';
        } elseif ($this->mentioned_quantity > 50) {
            return 'secondary';
        } else {
            return 'destructive';
        }
    }
}
