<?php

namespace App\Actions\Product;

use App\Models\Product;
use App\Repositories\ProductRepository;

class DestroyProductAction
{
    public function __construct(
        private ProductRepository $product_repository,
    ) {}

    public function execute(int $productId)
    {
        return $this->product_repository->delete($productId);
    }
}
