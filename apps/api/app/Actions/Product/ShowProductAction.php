<?php

namespace App\Actions\Product;

use App\Models\Product;
use App\Repositories\ProductRepository;

class ShowProductAction
{
    public function __construct(
        private ProductRepository $product_repository,
    ) {}

    public function execute(Product $product): Product
    {
        $this->product_repository->loadDefaultRelations($product);

        return $product;
    }
}
