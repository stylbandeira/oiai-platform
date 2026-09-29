<?php

namespace Tests\Unit\Services;

use App\Services\Product\ProductNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductNameNormalizerTest extends TestCase
{
    #[DataProvider('namesProvider')]
    public function test_extracts_quantity_and_packages(
        string $name,
        ?string $expectedQuantity,
        ?int $expectedPackages,
    ): void {
        $result = (new ProductNameNormalizer)->normalize($name);

        $this->assertSame($expectedQuantity, $result->normalizedQuantity);
        $this->assertSame($expectedPackages, $result->packageCount);
        $this->assertFalse($result->hasConflict);
    }

    public static function namesProvider(): array
    {
        return [
            'abbreviated grams' => ['ACHOCOLAT NESC 400GR', '400 g', null],
            'liter in full' => ['LEIT 1LITRO', '1000 ml', null],
            'multipack' => ['SABONETE 6X85G', '510 g', 6],
            'unknown can quantity' => ['COCA COLA LAT', null, null],
        ];
    }

    public function test_does_not_overwrite_registered_quantity_when_name_conflicts(): void
    {
        $result = (new ProductNameNormalizer)->normalize('PRODUTO 250G', '500 g');

        $this->assertTrue($result->hasConflict);
        $this->assertSame('500 g', $result->normalizedQuantity);
    }
}
