<?php

namespace App\Services\Product;

use App\Jobs\IndexProductJob;
use App\Models\Product;
use App\Models\ProductNormalizationDecision;
use App\Models\Unity;

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
            ->where('algorithm_version', '<=', $algorithmVersion)
            ->where('decision_source', 'manual')
            ->where('confidence', '>=', 0.8)
            ->latest('id')
            ->first();

        if (! $decision) {
            return false;
        }

        $values = array_intersect_key(
            (array) $decision->selected_values,
            array_flip(self::SAFE_FIELDS),
        );

        $this->applyQuantityAndUnity($product, $values);

        if ($values !== []) {
            $product->forceFill([
                ...$values,
                'normalization_validated_at' => now(),
            ])->saveQuietly();
        }

        return true;
    }

    /** @param array<string, mixed> $selectedValues */
    public function recordManualDecision(
        Product $product,
        array $selectedValues,
        int $reviewedBy,
        int $algorithmVersion,
        bool $validated = true,
    ): ProductNormalizationDecision {
        $rawName = (string) ($product->raw_name ?: $product->name);
        $safeValues = array_intersect_key($selectedValues, array_flip(self::SAFE_FIELDS));
        $validated = $this->isValidDecision($rawName, $safeValues);

        if ($validated) {
            $this->applyQuantityAndUnity($product, $safeValues);
            $product->forceFill(['normalization_validated_at' => now()])->saveQuietly();
        }

        $previousConfirmations = ProductNormalizationDecision::query()
            ->where('normalized_raw_name', $this->normalizedKey($rawName))
            ->where('algorithm_version', $algorithmVersion)
            ->where('decision_source', 'manual')
            ->get()
            ->filter(fn (ProductNormalizationDecision $decision): bool => $this->sameValues(
                (array) $decision->selected_values,
                $safeValues,
            ))
            ->count();
        $confirmationCount = $validated ? $previousConfirmations + 1 : 1;
        $confidence = $validated ? min(1.0, 0.6 + ($confirmationCount * 0.1)) : 0.0;

        return ProductNormalizationDecision::create([
            'product_id' => $product->getKey(),
            'raw_name' => $rawName,
            'normalized_raw_name' => $this->normalizedKey($rawName),
            'selected_values' => $safeValues,
            'decision_source' => $validated ? 'manual' : 'manual_unvalidated',
            'algorithm_version' => $algorithmVersion,
            'confidence' => $confidence,
            'confirmation_count' => $confirmationCount,
            'reviewed_by' => $reviewedBy,
        ]);
    }

    public function requiresDecision(Product $product): bool
    {
        return $product->normalization_validated_at === null;
    }

    /** @param array<string, mixed> $values */
    private function isValidDecision(string $rawName, array $values): bool
    {
        if (isset($values['normalized_name'])) {
            return $this->similarity($rawName, (string) $values['normalized_name']) >= 0.35;
        }

        if (isset($values['normalized_quantity'], $values['quantity_dimension'])) {
            $quantity = trim((string) $values['normalized_quantity']);

            return (
                is_numeric(str_replace(',', '.', $quantity))
                || preg_match('/^\d+(?:[,.]\d+)?\s*[a-z]{1,3}$/i', $quantity) === 1
            )
                && trim((string) $values['quantity_dimension']) !== '';
        }

        return false;
    }

    private function similarity(string $left, string $right): float
    {
        $left = $this->normalizedKey($left);
        $right = $this->normalizedKey($right);

        if ($left === '' || $right === '') {
            return 0.0;
        }

        return 1 - levenshtein($left, $right) / max(strlen($left), strlen($right));
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    private function sameValues(array $left, array $right): bool
    {
        ksort($left);
        ksort($right);

        return $left === $right;
    }

    /** @param array<string, mixed> $values */
    private function applyQuantityAndUnity(Product $product, array $values): void
    {
        if (! isset($values['normalized_quantity'], $values['quantity_dimension'])) {
            return;
        }

        $dimension = trim((string) $values['quantity_dimension']);
        $quantityText = preg_replace('/[^0-9,.]/', '', (string) $values['normalized_quantity']) ?: '0';
        $quantity = (float) str_replace(',', '.', $quantityText);
        $unity = Unity::query()
            ->whereRaw('LOWER(abbreviation) = ?', [mb_strtolower($dimension)])
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($dimension)])
            ->first();

        $normalizedName = (string) ($product->normalized_name ?: $product->name);
        $unitNames = [$dimension];
        if ($unity) {
            $unitNames[] = $unity->abbreviation;
            $unitNames[] = $unity->name;
        }
        $unitPattern = implode('|', array_map(
            static fn (string $unit): string => preg_quote($unit, '/'),
            array_unique(array_filter($unitNames)),
        ));
        $normalizedName = trim((string) preg_replace(
            '/\s*\d+(?:[,.]\d+)?\s*(?:'.$unitPattern.')\b/iu',
            '',
            $normalizedName,
        ));

        $product->forceFill([
            'normalized_quantity' => $values['normalized_quantity'],
            'quantity' => $quantity,
            'quantity_dimension' => $unity instanceof Unity ? $unity->dimension : $dimension,
            'unit_id' => $unity instanceof Unity ? $unity->getKey() : $product->unit_id,
            'name' => $normalizedName,
            'normalized_name' => $normalizedName,
        ])->saveQuietly();

        IndexProductJob::dispatch((int) $product->getKey());
    }
}
