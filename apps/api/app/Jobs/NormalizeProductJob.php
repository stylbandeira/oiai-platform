<?php

namespace App\Jobs;

use App\Events\ProductEnriched;
use App\Models\Product;
use App\Services\Product\ProductNameNormalizer;
use App\Services\Product\ProductNormalizationDecisionService;
use App\Services\Product\ProductOperationMonitor;
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
        ProductOperationMonitor $monitor,
    ): void {
        $product = Product::find($this->productId);
        if (! $product || $product->normalization_version >= self::VERSION) {
            return;
        }

        $monitor->measure('normalization', $product, function () use ($product, $decisions, $normalizer): void {
            $name = trim((string) ($product->raw_name ?: $product->name));
            $reusedDecision = $decisions->applyConfirmedDecision($product, self::VERSION);
            $normalization = $normalizer->normalize($name, $product->normalized_quantity);

            $updates = [];
            if (! $reusedDecision && $product->name_normalization_validated_at === null) {
                $updates['normalized_name'] = $normalization->normalizedName;
                $updates['search_description'] = trim((string) ($product->search_description ?: $product->description));
            }
            if (! $reusedDecision && $product->quantity_normalization_validated_at === null) {
                $updates['normalized_quantity'] = $normalization->normalizedQuantity;
                $updates['quantity_dimension'] = $normalization->quantityDimension ?? $product->quantity_dimension;
                $updates['package_count'] = $normalization->packageCount;
                $updates['normalization_conflict'] = $normalization->hasConflict;
            }
            if ($updates !== []) {
                $product->forceFill($updates)->saveQuietly();
            }

            $product->forceFill([
                'normalization_version' => self::VERSION,
                'normalized_at' => now(),
            ])->saveQuietly();
        });

        ProductEnriched::dispatch($this->productId);
        IndexProductJob::dispatch($this->productId);
    }
}
