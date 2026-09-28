<?php

namespace App\Jobs;

use App\Events\ProductEnriched;
use App\Models\Product;
use App\Services\Product\ProductNameNormalizer;
use App\Services\Product\ProductNormalizationDecisionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class NormalizeProductJob implements ShouldQueue
{
    use Queueable;

    public const VERSION = 3;

    public function __construct(public readonly int $productId) {}

    public function handle(
        ProductNormalizationDecisionService $decisions,
        ProductNameNormalizer $normalizer,
    ): void {
        $product = Product::find($this->productId);
        if (! $product || $product->normalization_version >= self::VERSION) {
            return;
        }

        $name = trim((string) ($product->raw_name ?: $product->name));
        $reusedDecision = $decisions->applyConfirmedDecision($product, self::VERSION);
        $normalization = $normalizer->normalize($name, $product->normalized_quantity);

        if (! $reusedDecision) {
            $product->forceFill([
                'normalized_name' => $normalization->normalizedName,
                'normalized_quantity' => $normalization->normalizedQuantity,
                'quantity_dimension' => $normalization->quantityDimension ?? $product->quantity_dimension,
                'package_count' => $normalization->packageCount,
                'normalization_conflict' => $normalization->hasConflict,
                'search_description' => trim((string) ($product->search_description ?: $product->description)),
            ])->saveQuietly();
        }

        $product->forceFill([
            'normalization_version' => self::VERSION,
            'normalized_at' => now(),
        ])->saveQuietly();

        ProductEnriched::dispatch($this->productId);
        IndexProductJob::dispatch($this->productId);
    }
}
