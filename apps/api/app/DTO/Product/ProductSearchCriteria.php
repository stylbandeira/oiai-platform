<?php

namespace App\DTO\Product;

final readonly class ProductSearchCriteria
{
    public function __construct(
        public string $query,
        public ?int $categoryId = null,
        public ?int $brandId = null,
        public ?string $dimension = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
