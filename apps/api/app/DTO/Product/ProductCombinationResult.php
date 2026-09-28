<?php

namespace App\DTO\Product;

final readonly class ProductCombinationResult
{
    /** @param list<array{offer_id: int, product_id: int, packages: int, package_base_quantity: float, unit_price: float}> $items */
    public function __construct(
        public array $items,
        public float $desiredBaseQuantity,
        public float $suppliedBaseQuantity,
        public float $excessBaseQuantity,
        public float $totalPrice,
    ) {}

    public static function noMatch(float $desiredBaseQuantity): self
    {
        return new self([], $desiredBaseQuantity, 0.0, 0.0, 0.0);
    }

    public function found(): bool
    {
        return $this->items !== [];
    }
}
