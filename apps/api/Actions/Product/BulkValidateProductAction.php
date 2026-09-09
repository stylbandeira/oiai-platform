<?php

namespace App\Actions\Product;


use App\Models\User;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;

class BulkValidateProductAction
{
    public function __construct(
        private UserRepository $user_repository,
        private ProductRepository $product_repository
    ) {}

    public function execute(User $user, array $data)
    {
        $productsToScore = $this->product_repository->validateProducts($data['product_ids'], $user->id);

        foreach ($productsToScore as $product) {
            $this->user_repository->addPoints($product->created_by, 3);
        }

        return $productsToScore;
    }
}
