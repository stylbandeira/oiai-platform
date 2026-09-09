<?php

namespace Tests\Feature\Jobs;

use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use App\Jobs\ProductDataSearchJob;
use App\Models\Product;
use App\Repositories\ProductCategoryRepository;
use App\Services\Product\ProductDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductDataSearchJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatic_refinement_respects_status_precedence(): void
    {
        $adminProduct = Product::factory()->create([
            'ean' => '7890000000001',
            'name' => 'Produto validado pelo admin',
            'refined' => ProductRefinementStatus::AdminValidated->value,
        ]);
        $cosmosProduct = Product::factory()->create([
            'ean' => '7890000000002',
            'name' => 'Produto validado pela Cosmos',
            'raw_name' => null,
            'normalized_quantity' => null,
            'ncm' => null,
            'refined' => ProductRefinementStatus::CosmosValidated->value,
        ]);
        $oscbrProduct = Product::factory()->create([
            'ean' => '7890000000003',
            'name' => 'Produto já validado pela OSCBR',
            'refined' => ProductRefinementStatus::OscbrValidated->value,
        ]);
        $unrefinedProduct = Product::factory()->create([
            'ean' => '7890000000004',
            'name' => 'Produto sem refinamento',
            'refined' => ProductRefinementStatus::Unrefined->value,
        ]);

        $oscbrResult = [
            'name' => 'Produto retornado pela OSCBR',
            'raw_name' => 'Produto retornado pela OSCBR',
            'normalized_name' => 'produto retornado pela oscbr',
            'search_description' => 'produto retornado pela oscbr',
            'normalized_quantity' => '1000 g',
            'quantity_dimension' => 'mass',
            'ncm' => '17019900',
            'quantity_source' => ProductQuantitySource::DefaultExtraction->value,
            'quantity_confidence' => ProductQuantitySource::DefaultExtraction->confidence(),
            'refined' => ProductRefinementStatus::OscbrValidated->value,
        ];

        $productDataService = Mockery::mock(ProductDataService::class);
        $productDataService->shouldReceive('startRun')->once();
        $productDataService->shouldReceive('hasAvailableProvider')->times(3)->andReturnTrue();
        $productDataService->shouldReceive('maximumProductsPerRun')
            ->once()
            ->andReturn(12);
        $productDataService->shouldReceive('getProductData')
            ->once()
            ->andReturn($oscbrResult);
        $productDataService->shouldReceive('getUpgradeProductData')
            ->once()
            ->andReturn($oscbrResult);
        $productDataService->shouldReceive('getSupplementalProductData')
            ->once()
            ->andReturn($oscbrResult);
        $categoryRepository = Mockery::mock(ProductCategoryRepository::class);

        (new ProductDataSearchJob())->handle($productDataService, $categoryRepository);

        $this->assertSame('Produto validado pelo admin', $adminProduct->fresh()->name);
        $this->assertSame('Produto validado pela Cosmos', $cosmosProduct->fresh()->name);
        $this->assertSame('Produto retornado pela OSCBR', $cosmosProduct->fresh()->raw_name);
        $this->assertSame('1000 g', $cosmosProduct->fresh()->normalized_quantity);
        $this->assertSame('17019900', $cosmosProduct->fresh()->ncm);
        $this->assertSame(
            ProductRefinementStatus::CosmosValidated,
            $cosmosProduct->fresh()->refined,
        );
        $this->assertSame('Produto já validado pela OSCBR', $oscbrProduct->fresh()->name);
        $this->assertSame('Produto retornado pela OSCBR', $unrefinedProduct->fresh()->name);
        $this->assertSame(
            ProductRefinementStatus::OscbrValidated,
            $unrefinedProduct->fresh()->refined,
        );
    }

    public function test_marks_product_as_not_found_after_all_providers_return_404(): void
    {
        $product = Product::factory()->create([
            'ean' => '7896116900029',
            'refined' => ProductRefinementStatus::Unrefined,
        ]);
        $attempts = [
            [
                'provider' => 'cosmos',
                'status' => 'not_found',
                'http_status' => 404,
                'message' => 'Produto não encontrado',
            ],
            [
                'provider' => 'oscbr',
                'status' => 'not_found',
                'http_status' => 404,
                'message' => 'Produto não encontrado',
            ],
        ];

        $productDataService = Mockery::mock(ProductDataService::class);
        $productDataService->shouldReceive('startRun')->once();
        $productDataService->shouldReceive('hasAvailableProvider')
            ->once()
            ->with(ProductRefinementStatus::Unrefined, [])
            ->andReturnTrue();
        $productDataService->shouldReceive('maximumProductsPerRun')->once()->andReturn(12);
        $productDataService->shouldReceive('getProductData')
            ->once()
            ->andReturn(['_provider_attempts' => $attempts]);
        $productDataService->shouldReceive('hasAvailableProvider')
            ->once()
            ->with(ProductRefinementStatus::Unrefined, ['cosmos', 'oscbr'])
            ->andReturnFalse();

        (new ProductDataSearchJob())->handle(
            $productDataService,
            Mockery::mock(ProductCategoryRepository::class),
        );

        $this->assertSame(ProductRefinementStatus::NotFound, $product->fresh()->refined);
        $this->assertDatabaseCount('product_data_provider_attempts', 2);
        $this->assertDatabaseHas('product_data_provider_attempts', [
            'product_id' => $product->id,
            'provider' => 'cosmos',
            'status' => 'not_found',
            'http_status' => 404,
            'attempts' => 1,
        ]);
        $this->assertDatabaseHas('product_data_provider_attempts', [
            'product_id' => $product->id,
            'provider' => 'oscbr',
            'status' => 'not_found',
            'http_status' => 404,
            'attempts' => 1,
        ]);
    }
}
