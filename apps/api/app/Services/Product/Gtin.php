<?php

namespace App\Services\Product;

final class Gtin
{
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);
        $length = strlen($value);

        if (! in_array($length, [8, 12, 13, 14], true) || ! ctype_digit($value)) {
            return null;
        }

        // Restricted-circulation numbers for variable-weight retail items are
        // local to the issuer, not globally identifying GTINs.
        if (strspn($value, '0') === $length || ($length === 13 && $value[0] === '2')) {
            return null;
        }

        $sum = 0;
        for ($index = $length - 2, $weight = 3; $index >= 0; $index--, $weight = $weight === 3 ? 1 : 3) {
            $sum += (int) $value[$index] * $weight;
        }

        return (10 - $sum % 10) % 10 === (int) $value[$length - 1] ? $value : null;
    }
}
