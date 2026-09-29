<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Support\Collection;

final class ProductSearchRanker
{
    public function __construct(private SearchQueryNormalizer $normalizer) {}

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    public function rank(Collection $products, string $query): Collection
    {
        $normalizedQuery = $this->normalizer->normalize($query);
        $queryTokens = $this->normalizer->tokens($query);

        return $products
            ->filter(fn (Product $product): bool => $this->score($product, $normalizedQuery, $queryTokens) >= 0.55)
            ->sortByDesc(fn (Product $product): float => $this->score($product, $normalizedQuery, $queryTokens))
            ->values();
    }

    /** @param list<string> $queryTokens */
    private function score(Product $product, string $query, array $queryTokens): float
    {
        if ($query !== '' && $query === (string) $product->ean) {
            return 100.0;
        }

        $name = $this->normalizer->normalize((string) $product->name);
        $description = $this->normalizer->normalize((string) $product->description);
        $document = trim($name.' '.$description);
        if ($query === $name) {
            return 90.0;
        }
        if ($query !== '' && str_contains($name, $query)) {
            return 80.0;
        }

        $documentTokens = array_values(array_filter(explode(' ', $document)));
        if ($queryTokens === [] || $documentTokens === []) {
            return 0.0;
        }

        $scores = array_map(function (string $queryToken) use ($documentTokens): float {
            return max(array_map(
                fn (string $documentToken): float => $this->tokenSimilarity($queryToken, $documentToken),
                $documentTokens,
            ));
        }, $queryTokens);

        if (min($scores) < 0.55) {
            return 0.0;
        }

        $average = array_sum($scores) / count($scores);

        return $average
            + ($product->validated ? 0.05 : 0.0)
            + min(0.05, ((int) $product->mentioned_quantity) / 1000);
    }

    private function tokenSimilarity(string $left, string $right): float
    {
        if ($left === $right) {
            return 1.0;
        }
        if (str_starts_with($right, $left) || str_starts_with($left, $right)) {
            return 0.9;
        }

        return 1 - levenshtein($left, $right) / max(strlen($left), strlen($right));
    }
}
