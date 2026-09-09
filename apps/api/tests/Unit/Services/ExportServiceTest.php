<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Services\ExportService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class ExportServiceTest extends TestCase
{
    public function test_export_to_csv_returns_streamed_response_with_headers_and_rows(): void
    {
        $service = new ExportService();
        $product = new Product([
            'name' => 'Arroz',
            'sku' => 'SKU-1',
            'average_price' => 12.5,
        ]);

        $response = $service->exportToCSV(
            new Collection([$product]),
            [
                'Nome' => 'name',
                'SKU' => 'sku',
                'Preço' => fn(Product $item) => number_format($item->average_price, 2),
                'Categoria' => 'category.name',
            ],
            'produtos'
        );

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="produtos_', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Nome;SKU;Preço;Categoria', $content);
        $this->assertStringContainsString('Arroz;SKU-1;12.50;N/A', $content);
    }

    public function test_simple_export_delegates_to_csv_export(): void
    {
        $service = new ExportService();

        $response = $service->simpleExport(
            new Collection([new Product(['name' => 'Feijao'])]),
            ['Nome' => 'name'],
            'simples'
        );

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Nome', $content);
        $this->assertStringContainsString('Feijao', $content);
    }
}
