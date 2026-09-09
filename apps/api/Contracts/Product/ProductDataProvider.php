<?php

namespace App\Contracts\Product;

use App\Enums\ProductRefinementStatus;

interface ProductDataProvider
{
    public function key(): string;

    public function refinementStatus(): ProductRefinementStatus;

    public function batchSize(): int;

    public function recurrenceMinutes(): int;

    public function dailyLimit(): int;

    public function getProductData(string $ean): array;
}
