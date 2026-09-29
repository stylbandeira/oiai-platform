<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductType extends BaseModel
{
    use HasFactory;

    protected $fillable = ['name', 'normalized_name', 'description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ShoppingListRequirement::class);
    }
}
