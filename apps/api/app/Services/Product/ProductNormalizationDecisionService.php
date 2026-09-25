<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductNormalizationDecision;

final class ProductNormalizationDecisionService
{
    /** @var list<string> */
    private const SAFE_FIELDS = [
        'normalized_name',
        'normalized_quantity',
        'quantity_dimension',
        'search_description',
    ];

    public function normalizedKey(string $rawName): string
    {
        $value = mb_strtolower(trim($rawName));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;

        return preg_replace('/\s+/', ' ', $value) ?: $value;
    }

    public function applyConfirmedDecision(Product $product, int $algorithmVersion): bool
    {
        $rawName = (string) ($product->raw_name ?: $product->name);
        $decision = ProductNormalizationDecision::query()
            ->where('normalized_raw_name', $this->normalizedKey($rawName))
            ->where('algorithm_version', $algorithmVersion)
            ->where('decision_source', 'manual')
            ->latest('id')
            ->first();

        if (! $decision) {
            return false;
        }

        $values = array_intersect_key(
            (array) $decision->selected_values,
            array_flip(self::SAFE_FIELDS),
        );

        if ($values !== []) {
            $product->forceFill($values)->saveQuietly();
        }

        return true;
    }

    /** @param array<string, mixed> $selectedValues */
    public function recordManualDecision(
        Product $product,
        array $selectedValues,
        int $reviewedBy,
        int $algorithmVersion,
    ): ProductNormalizationDecision {
        $rawName = (string) ($product->raw_name ?: $product->name);
        $safeValues = array_intersect_key($selectedValues, array_flip(self::SAFE_FIELDS));

        return ProductNormalizationDecision::create([
            'product_id' => $product->getKey(),
            'raw_name' => $rawName,
            'normalized_raw_name' => $this->normalizedKey($rawName),
            'selected_values' => $safeValues,
            'decision_source' => 'manual',
            'algorithm_version' => $algorithmVersion,
            'reviewed_by' => $reviewedBy,
        ]);
    }
}
