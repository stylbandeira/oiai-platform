<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;

final class ProductCreated
{
    use Dispatchable;
    public function __construct(public readonly int $productId)
    {
    }

    public static function fromProduct(Product $product): self
    {
        return new self((int) $product->getKey());
    }
}
