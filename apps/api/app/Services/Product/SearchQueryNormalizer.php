<?php

namespace App\Services\Product;

use Illuminate\Support\Str;

final class SearchQueryNormalizer
{
    public function normalize(string $query): string
    {
        $normalized = Str::lower(Str::ascii(trim($query)));
        $normalized = preg_replace('/(\d)\s+(kg|mg|g|ml|l)\b/', '$1$2', $normalized) ?? $normalized;

        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }

    /** @return list<string> */
    public function tokens(string $query): array
    {
        return array_values(array_filter(explode(' ', $this->normalize($query))));
    }
}
