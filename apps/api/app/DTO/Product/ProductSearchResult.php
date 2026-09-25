<?php

namespace App\DTO\Product;

use App\Models\Product;

final readonly class ProductSearchResult
{
    /** @param list<int> $ids */
    public function __construct(
        public array $ids,
        public ?Product $exactProduct = null,
    ) {}

    public static function fromExactProduct(Product $product): self
    {
        return new self([(int) $product->getKey()], $product);
    }
}
