<?php

namespace App\Observers;

use App\Events\ProductCreated;
use App\Events\ProductUpdated;
use App\Jobs\NormalizeProductJob;
use App\Models\Product;
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

    public function created(Product $product): void
    {
        ProductCreated::dispatch((int) $product->getKey());
        NormalizeProductJob::dispatch((int) $product->getKey());
    }

    public function updating(Product $product)
    {
        if ($product->getOriginal('validated') === false && ! $product->validated_by) {
            $this->userRepo->addPoints($product->created_by, 3);
        }
    }

    public function updated(Product $product): void
    {
        ProductUpdated::dispatch((int) $product->getKey());
        NormalizeProductJob::dispatch((int) $product->getKey());
    }
}
