<?php

namespace App\Observers;

use App\Models\CompanyProducts;

final class CompanyProductsObserver
{
    public function saved(CompanyProducts $offer): void
    {
        if ($offer->wasChanged('average_price') && ! $offer->wasChanged('current_price')) {
            $offer->current_price = $offer->average_price;
            $offer->saveQuietly();
        }

        $offer->recalculatePricePerBaseUnit();
    }
}
