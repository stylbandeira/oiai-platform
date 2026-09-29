<?php

namespace App\Contracts\Product;

use App\DTO\Product\ProductCombinationResult;
use App\Models\ShoppingListRequirement;
use Illuminate\Support\Collection;

interface ProductCombinationOptimizer
{
    /** @param Collection<int, mixed> $offers */
    public function optimize(
        ShoppingListRequirement $requirement,
        Collection $offers,
    ): ProductCombinationResult;
}
