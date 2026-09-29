<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Services\Product\ProductSearchRanker;
use App\Services\Product\SearchQueryNormalizer;
use Tests\TestCase;

class ProductSearchRankerTest extends TestCase
{
    public function test_quality_dataset_places_expected_product_within_expected_rank(): void
    {
        $dataset = require dirname(__DIR__, 2).'/Fixtures/search_quality_dataset.php';
        $products = collect(array_map(function (array $case, int $index): Product {
            return (new Product)->forceFill([
                'id' => $index + 1,
                'name' => $case['expected'],
                'ean' => $case['expected'] === 'Produto EAN exato' ? '7891000100103' : null,
                'description' => $case['expected'],
                'validated' => true,
            ]);
        }, $dataset, array_keys($dataset)));
        $products->push((new Product)->forceFill([
            'id' => 999,
            'name' => 'Detergente neutro 500 ml',
            'description' => 'Produto não relacionado',
        ]));
        $ranker = new ProductSearchRanker(new SearchQueryNormalizer);

        foreach ($dataset as $case) {
            $ranked = $ranker->rank($products, $case['query']);
            $position = $ranked->search(fn (Product $product): bool => $product->name === $case['expected']);

            $this->assertNotFalse($position, "Resultado ausente para {$case['query']}");
            $this->assertLessThanOrEqual($case['max_rank'], $position + 1, "Ranking incorreto para {$case['query']}");
            $this->assertNotSame(999, $ranked->first()?->id, "Falso positivo no topo para {$case['query']}");
        }
    }
}
