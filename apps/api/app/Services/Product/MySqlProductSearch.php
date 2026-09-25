<?php

namespace App\Services\Product;

use App\Contracts\Product\ProductSearch;
use App\DTO\Product\ProductSearchCriteria;
use App\DTO\Product\ProductSearchResult;
use App\Models\Product;

final class MySqlProductSearch implements ProductSearch
{
    public function search(ProductSearchCriteria $criteria): ProductSearchResult
    {
        $query = Product::query();
        $term = trim($criteria->query);

        if (preg_match('/^\d{8,14}$/', $term) === 1) {
            $product = (clone $query)->where('ean', $term)->first();

            return $product ? ProductSearchResult::fromExactProduct($product) : new ProductSearchResult([]);
        }

        if ($term !== '') {
            $words = preg_split('/\s+/', mb_strtolower($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $query->where(function ($search) use ($term, $words): void {
                $search->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', $term)
                    ->orWhere('description', 'like', '%'.$term.'%');

                foreach ($words as $word) {
                    $search->orWhere('name', 'like', '%'.$word.'%')
                        ->orWhere('description', 'like', '%'.$word.'%');
                }
            });
        }

        if ($criteria->categoryId !== null) {
            $query->where('category_id', $criteria->categoryId);
        }

        if ($criteria->dimension !== null) {
            $query->where('quantity_dimension', $criteria->dimension);
        }

        return new ProductSearchResult(
            $query->orderByDesc('validated')
                ->orderByDesc('mentioned_quantity')
                ->orderBy('name')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all()
        );
    }
}
