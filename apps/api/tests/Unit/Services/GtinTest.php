<?php

namespace Tests\Unit\Services;

use App\Services\Product\Gtin;
use PHPUnit\Framework\TestCase;

class GtinTest extends TestCase
{
    public function test_accepts_a_valid_gtin(): void
    {
        $this->assertSame('7891000100103', Gtin::normalize(' 7891000100103 '));
    }

    public function test_rejects_missing_invalid_and_restricted_codes(): void
    {
        foreach (['SEM GTIN', '', '0', '0000000000000', '7891234567890', '2000000000004'] as $value) {
            $this->assertNull(Gtin::normalize($value), $value);
        }
    }
}
