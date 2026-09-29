<?php

namespace App\Services\Product;

use App\Contracts\Product\ProductSearch;
use App\DTO\Product\ProductSearchCriteria;
use App\DTO\Product\ProductSearchResult;
use App\Models\Product;

final class MySqlProductSearch implements ProductSearch
{
    public function __construct(private ProductSearchRanker $ranker) {}

    public function search(ProductSearchCriteria $criteria): ProductSearchResult
    {
        $query = Product::query();
        $term = trim($criteria->query);

        if (preg_match('/^\d{8,14}$/', $term) === 1) {
            $product = (clone $query)->where('ean', $term)->first();

            return $product ? ProductSearchResult::fromExactProduct($product) : new ProductSearchResult([]);
        }

        if ($criteria->categoryId !== null) {
            $query->where('category_id', $criteria->categoryId);
        }

        if ($criteria->dimension !== null) {
            $query->where('quantity_dimension', $criteria->dimension);
        }

        if ($criteria->brandId !== null) {
            $query->where('brand_id', $criteria->brandId);
        }

        $products = $query->limit(1500)->get();

        return new ProductSearchResult(
            $this->ranker->rank($products, $term)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all()
        );
    }
}
