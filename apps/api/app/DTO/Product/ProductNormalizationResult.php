<?php

namespace App\DTO\Product;

final readonly class ProductNormalizationResult
{
    public function __construct(
        public string $normalizedName,
        public ?string $normalizedQuantity,
        public ?string $quantityDimension,
        public ?int $packageCount,
        public bool $hasConflict,
    ) {}
}
