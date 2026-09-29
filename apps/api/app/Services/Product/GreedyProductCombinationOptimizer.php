<?php

namespace App\Services\Product;

use App\Contracts\Product\ProductCombinationOptimizer;
use App\DTO\Product\ProductCombinationResult;
use App\Models\CompanyProducts;
use App\Models\Product;
use App\Models\ShoppingListRequirement;
use App\Models\Unity;
use Illuminate\Support\Collection;

final class GreedyProductCombinationOptimizer implements ProductCombinationOptimizer
{
    public function optimize(ShoppingListRequirement $requirement, Collection $offers): ProductCombinationResult
    {
        $requirement->loadMissing('desiredUnit');
        /** @var Unity $desiredUnit */
        $desiredUnit = $requirement->desiredUnit;
        $desiredBaseQuantity = $desiredUnit->toBaseQuantity((float) $requirement->desired_quantity);

        $candidates = $offers
            ->filter(fn ($offer): bool => $offer instanceof CompanyProducts)
            ->each(fn (CompanyProducts $offer) => $offer->loadMissing('product.unity'))
            ->filter(function (CompanyProducts $offer) use ($requirement, $desiredUnit): bool {
                /** @var Product|null $product */
                $product = $offer->product;
                if (! $product || ! $product->unity || $product->product_type_id !== $requirement->product_type_id) {
                    return false;
                }
                if ($product->unity->dimension !== $desiredUnit->dimension) {
                    return false;
                }
                if ($requirement->specific_product_id !== null && $product->id !== $requirement->specific_product_id) {
                    return false;
                }
                if ($requirement->brand_id !== null && $product->brand_id !== $requirement->brand_id) {
                    return false;
                }
                if ($requirement->variant_id !== null && $product->variant_id !== $requirement->variant_id) {
                    return false;
                }

                return $offer->effectivePrice() > 0 && $product->baseQuantity() > 0;
            })
            ->map(function (CompanyProducts $offer) use ($desiredBaseQuantity): array {
                $packageBaseQuantity = (float) $offer->product->baseQuantity();
                $packages = (int) ceil($desiredBaseQuantity / $packageBaseQuantity);
                $supplied = $packages * $packageBaseQuantity;
                $price = $offer->effectivePrice();

                return compact('offer', 'packageBaseQuantity', 'packages', 'supplied', 'price') + [
                    'total' => $packages * $price,
                    'excess' => $supplied - $desiredBaseQuantity,
                ];
            })
            ->sortBy(fn (array $candidate): array => [
                $candidate['total'], $candidate['excess'], $candidate['packages'],
            ]);

        $best = $candidates->first();
        if (! $best) {
            return ProductCombinationResult::noMatch($desiredBaseQuantity);
        }

        /** @var CompanyProducts $offer */
        $offer = $best['offer'];

        return new ProductCombinationResult(
            items: [[
                'offer_id' => (int) $offer->id,
                'product_id' => (int) $offer->product_id,
                'packages' => $best['packages'],
                'package_base_quantity' => $best['packageBaseQuantity'],
                'unit_price' => $best['price'],
            ]],
            desiredBaseQuantity: $desiredBaseQuantity,
            suppliedBaseQuantity: $best['supplied'],
            excessBaseQuantity: $best['excess'],
            totalPrice: $best['total'],
        );
    }
}
