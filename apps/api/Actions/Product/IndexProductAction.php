<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductIndexRequest;
use App\Models\User;
use App\Repositories\ProductRepository;

class IndexProductAction
{
    public function __construct(private ProductRepository $productRepo) {}

    public function execute(User $user, array $request)
    {
        return $this->productRepo->paginate($user, $request);
    }
}
