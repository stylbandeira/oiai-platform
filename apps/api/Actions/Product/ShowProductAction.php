<?php

namespace App\Actions\Product;

use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Auth;

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
