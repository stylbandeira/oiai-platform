<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class ProductEnriched
{
    use Dispatchable;
    public function __construct(public readonly int $productId)
    {
    }
}
