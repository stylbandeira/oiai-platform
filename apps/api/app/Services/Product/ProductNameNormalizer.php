<?php

namespace App\Services\Product;

use App\DTO\Product\ProductNormalizationResult;

final class ProductNameNormalizer
{
    public function normalize(string $rawName, ?string $registeredQuantity = null): ProductNormalizationResult
    {
        $name = trim((string) preg_replace('/\s+/', ' ', $rawName));
        $extracted = $this->extractQuantity($name);
        $hasConflict = $registeredQuantity !== null
            && $extracted !== null
            && $this->comparableQuantity($registeredQuantity) !== $this->comparableQuantity($extracted['quantity']);

        return new ProductNormalizationResult(
            normalizedName: $name,
            normalizedQuantity: $hasConflict ? $registeredQuantity : ($registeredQuantity ?? $extracted['quantity'] ?? null),
            quantityDimension: $extracted['dimension'] ?? null,
            packageCount: $extracted['packages'] ?? null,
            hasConflict: $hasConflict,
        );
    }

    /** @return array{quantity: string, dimension: string, packages: int|null}|null */
    private function extractQuantity(string $name): ?array
    {
        $unitPattern = 'MILIGRAMAS?|MG|QUILOGRAMAS?|KGS?|KG|GRAMAS?|GRS?|GR|G|MILILITROS?|MLS?|ML|LITROS?|LTS?|LT|L';

        if (preg_match('/(\d+)\s*[X×]\s*(\d+(?:[,.]\d+)?)\s*('.$unitPattern.')\b/iu', $name, $match)) {
            $packages = (int) $match[1];
            [$value, $unit, $dimension] = $this->canonical((float) str_replace(',', '.', $match[2]), $match[3]);

            return [
                'quantity' => $this->format($value * $packages).' '.$unit,
                'dimension' => $dimension,
                'packages' => $packages,
            ];
        }

        if (preg_match('/(\d+(?:[,.]\d+)?)\s*('.$unitPattern.')\b/iu', $name, $match)) {
            [$value, $unit, $dimension] = $this->canonical((float) str_replace(',', '.', $match[1]), $match[2]);

            return [
                'quantity' => $this->format($value).' '.$unit,
                'dimension' => $dimension,
                'packages' => null,
            ];
        }

        return null;
    }

    /** @return array{float, string, string} */
    private function canonical(float $value, string $unit): array
    {
        $unit = mb_strtolower($unit);

        return match (true) {
            preg_match('/^(kg|kgs|quilograma|quilogramas)$/', $unit) === 1 => [$value * 1000, 'g', 'mass'],
            preg_match('/^(mg|miligrama|miligramas)$/', $unit) === 1 => [$value, 'mg', 'mass'],
            preg_match('/^(g|gr|grs|grama|gramas)$/', $unit) === 1 => [$value, 'g', 'mass'],
            preg_match('/^(l|lt|lts|litro|litros)$/', $unit) === 1 => [$value * 1000, 'ml', 'volume'],
            default => [$value, 'ml', 'volume'],
        };
    }

    private function comparableQuantity(string $quantity): string
    {
        $extracted = $this->extractQuantity($quantity);

        return mb_strtolower($extracted['quantity'] ?? trim($quantity));
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
