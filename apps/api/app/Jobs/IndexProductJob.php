<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class IndexProductJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $productId)
    {
    }

    public function handle(): void
    {
        $product = Product::find($this->productId);
        if ($product) {
            $product->searchable();
        }
    }
}
