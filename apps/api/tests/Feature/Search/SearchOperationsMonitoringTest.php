<?php

namespace Tests\Feature\Search;

use App\Jobs\IndexProductsBatchJob;
use App\Models\Product;
use App\Models\ProductPipelineRun;
use App\Services\Product\ProductOperationMonitor;
use App\Services\Product\SearchInfrastructureMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SearchOperationsMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_indexing_updates_version_and_records_duration(): void
    {
        $products = Product::withoutEvents(fn () => Product::factory()->count(2)->create([
            'search_document_version' => 0,
            'search_indexed_at' => null,
        ]));

        (new IndexProductsBatchJob($products->modelKeys()))->handle(app(ProductOperationMonitor::class));

        $this->assertSame(0, Product::query()->whereNull('search_indexed_at')->count());
        $this->assertSame(0, Product::query()->where('search_document_version', '<', Product::SEARCH_DOCUMENT_VERSION)->count());
        $this->assertDatabaseHas('product_pipeline_runs', [
            'operation' => 'indexing',
            'status' => 'completed',
        ]);
        $this->assertGreaterThanOrEqual(1, ProductPipelineRun::query()->sole()->duration_ms);
    }

    public function test_failed_indexing_operation_is_traceable(): void
    {
        $product = Product::withoutEvents(fn () => Product::factory()->create());
        try {
            app(ProductOperationMonitor::class)->measure(
                'indexing',
                $product,
                static fn () => throw new RuntimeException('Meilisearch unavailable'),
            );
        } catch (RuntimeException $exception) {
            $this->assertSame('Meilisearch unavailable', $exception->getMessage());
        }

        $this->assertDatabaseHas('product_pipeline_runs', [
            'product_id' => $product->id,
            'operation' => 'indexing',
            'status' => 'failed',
            'error_message' => 'Meilisearch unavailable',
        ]);
    }

    public function test_counts_products_with_outdated_search_documents(): void
    {
        Product::withoutEvents(fn () => Product::factory()->create([
            'search_document_version' => 0,
            'search_indexed_at' => null,
        ]));
        Product::withoutEvents(fn () => Product::factory()->create([
            'search_document_version' => Product::SEARCH_DOCUMENT_VERSION,
            'search_indexed_at' => now()->addSecond(),
        ]));

        $this->assertSame(1, app(SearchInfrastructureMonitor::class)->staleProductCount());
    }
}
