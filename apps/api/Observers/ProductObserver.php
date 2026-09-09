<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\UserAddedProducts;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;

class ProductObserver
{
    protected $user;
    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->user = Auth::user();
        $this->userRepo = $userRepo;
    }
    public function creating(Product $product)
    {
        $product->mentioned_quantity++;
    }

    public function updating(Product $product)
    {
        if ($product->getOriginal('validated') === false && !$product->validated_by) {
            $this->userRepo = UserRepository::class;
            $this->userRepo->addPoints($product->created_by, 3);
        }
    }
}
