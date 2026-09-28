<?php

namespace App\DTO\Product;

use App\Models\Product;

final readonly class ProductSearchResult
{
    /** @param list<int> $ids */
    public function __construct(
        public array $ids,
        public ?Product $exactProduct = null,
        public string $engine = 'mysql',
        public bool $fallbackUsed = false,
    ) {}

    public static function fromExactProduct(Product $product): self
    {
        return new self([(int) $product->getKey()], $product, 'mysql_exact');
    }

    public function asFallback(): self
    {
        return new self($this->ids, $this->exactProduct, $this->engine, true);
    }
}
