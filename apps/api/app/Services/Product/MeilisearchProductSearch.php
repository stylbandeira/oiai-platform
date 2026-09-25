<?php

namespace App\Services\Product;

use App\Contracts\Product\ProductSearch;
use App\DTO\Product\ProductSearchCriteria;
use App\DTO\Product\ProductSearchResult;
use App\Models\Product;
use Throwable;

final class MeilisearchProductSearch implements ProductSearch
{
    public function __construct(private MySqlProductSearch $fallback) {}

    public function search(ProductSearchCriteria $criteria): ProductSearchResult
    {
        $term = trim($criteria->query);

        // Identificadores nunca passam por fuzzy search.
        if (preg_match('/^\d{8,14}$/', $term) === 1) {
            return $this->fallback->search($criteria);
        }

        try {
            $builder = Product::search($term);

            if ($criteria->categoryId !== null) {
                $builder->where('category_id', $criteria->categoryId);
            }

            if ($criteria->dimension !== null) {
                $builder->where('quantity_dimension', $criteria->dimension);
            }

            return new ProductSearchResult(
                $builder->get()->pluck('id')->map(static fn ($id): int => (int) $id)->all()
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->fallback->search($criteria);
        }
    }
}
