<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class ProductAliasCreated
{
    use Dispatchable;
    public function __construct(public readonly int $productId)
    {
    }
}
