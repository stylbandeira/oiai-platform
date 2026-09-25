<?php

namespace App\Contracts\Product;

use App\DTO\Product\ProductSearchCriteria;
use App\DTO\Product\ProductSearchResult;

interface ProductSearch
{
    public function search(ProductSearchCriteria $criteria): ProductSearchResult;
}
