<?php

namespace Tests\Unit\Services;

use App\Contracts\Product\ProductDataProvider;
use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use App\Services\Product\ProductDataService;
use App\Services\Product\Providers\CosmosProductDataProvider;
use App\Services\Product\Providers\OscbrProductDataProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class ProductDataServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cosmos_provider_normalizes_product_enrichment_fields(): void
    {
        $data = (new CosmosProductDataProvider())->normalize([
            'description' => 'AÇÚCAR REFINADO UNIÃO 1KG',
            'brand' => ['name' => 'UNIÃO'],
            'gpc' => ['description' => 'Açúcar'],
            'ncm' => [
                'code' => '17019900',
                'full_description' => 'Açúcares e produtos de confeitaria',
            ],
            'net_weight' => 1000,
            'thumbnail' => 'https://cdn.example/product.png',
        ]);

        $source = ProductQuantitySource::CosmosExtraction;

        $this->assertSame('AÇÚCAR REFINADO UNIÃO 1KG', $data['raw_name']);
        $this->assertSame('acucar refinado uniao 1kg', $data['normalized_name']);
        $this->assertSame('1000 g', $data['normalized_quantity']);
        $this->assertSame('mass', $data['quantity_dimension']);
        $this->assertSame($source->value, $data['quantity_source']);
        $this->assertSame($source->confidence(), $data['quantity_confidence']);
        $this->assertSame(ProductRefinementStatus::CosmosValidated->value, $data['refined']);
        $this->assertSame('17019900', $data['ncm']);
        $this->assertStringNotContainsString('17019900', $data['search_description']);
    }

    public function test_service_uses_next_provider_when_first_does_not_find_product(): void
    {
        $cosmos = Mockery::mock(ProductDataProvider::class);
        $this->configureUsagePolicy($cosmos, 'cosmos');
        $cosmos->shouldReceive('getProductData')->once()->andReturn([]);
        $oscbr = Mockery::mock(ProductDataProvider::class);
        $this->configureUsagePolicy($oscbr, 'oscbr');
        $oscbr->shouldReceive('getProductData')->once()->andReturn([
            'name' => 'Produto OSCBR',
            'refined' => ProductRefinementStatus::OscbrValidated->value,
        ]);

        $data = (new ProductDataService([$cosmos, $oscbr]))
            ->getProductData('7896116900029');

        $this->assertSame('Produto OSCBR', $data['name']);
    }

    public function test_service_stops_cascade_when_cosmos_finds_product(): void
    {
        $cosmos = Mockery::mock(ProductDataProvider::class);
        $this->configureUsagePolicy($cosmos, 'cosmos');
        $cosmos->shouldReceive('getProductData')->once()->andReturn([
            'name' => 'Produto Cosmos',
            'refined' => ProductRefinementStatus::CosmosValidated->value,
        ]);
        $oscbr = Mockery::mock(ProductDataProvider::class);
        $oscbr->shouldNotReceive('getProductData');

        $data = (new ProductDataService([$cosmos, $oscbr]))
            ->getProductData('7896116900029');

        $this->assertSame('Produto Cosmos', $data['name']);
    }

    public function test_service_reports_definitive_not_found_for_each_provider(): void
    {
        $cosmos = Mockery::mock(ProductDataProvider::class);
        $this->configureUsagePolicy($cosmos, 'cosmos');
        $cosmos->shouldReceive('getProductData')->once()->andReturn([
            '_lookup' => [
                'status' => 'not_found',
                'http_status' => 404,
                'message' => 'Not found',
            ],
        ]);
        $oscbr = Mockery::mock(ProductDataProvider::class);
        $this->configureUsagePolicy($oscbr, 'oscbr');
        $oscbr->shouldReceive('getProductData')->once()->andReturn([
            '_lookup' => [
                'status' => 'not_found',
                'http_status' => 404,
                'message' => 'Produto não encontrado',
            ],
        ]);

        $data = (new ProductDataService([$cosmos, $oscbr]))
            ->getProductData('7896116900029');

        $this->assertSame(['cosmos', 'oscbr'], collect($data['_provider_attempts'])
            ->pluck('provider')->all());
        $this->assertSame([404, 404], collect($data['_provider_attempts'])
            ->pluck('http_status')->all());
    }

    public function test_service_respects_provider_batch_size_and_recurrence(): void
    {
        $provider = Mockery::mock(ProductDataProvider::class);
        $this->configureUsagePolicy($provider, 'limited', batchSize: 2, recurrenceMinutes: 5);
        $provider->shouldReceive('getProductData')->twice()->andReturn([]);

        $service = new ProductDataService([$provider]);
        $service->getProductData('1');
        $service->getProductData('2');
        $service->getProductData('3');

        $nextRunProvider = Mockery::mock(ProductDataProvider::class);
        $this->configureUsagePolicy(
            $nextRunProvider,
            'limited',
            batchSize: 2,
            recurrenceMinutes: 5,
        );
        $nextRunProvider->shouldNotReceive('getProductData');

        (new ProductDataService([$nextRunProvider]))->getProductData('4');

        $this->assertSame(2, Cache::get('product-data:limited:daily:'.now()->format('Y-m-d')));

        Carbon::setTestNow(now()->addMinutes(6));
        $eligibleProvider = Mockery::mock(ProductDataProvider::class);
        $this->configureUsagePolicy(
            $eligibleProvider,
            'limited',
            batchSize: 2,
            recurrenceMinutes: 5,
        );
        $eligibleProvider->shouldReceive('getProductData')->once()->andReturn([]);

        (new ProductDataService([$eligibleProvider]))->getProductData('5');

        $this->assertSame(3, Cache::get('product-data:limited:daily:'.now()->format('Y-m-d')));
    }

    public function test_oscbr_provider_authenticates_before_every_product_request(): void
    {
        config()->set([
            'services.oscbr.login' => 'api-user',
            'services.oscbr.password' => 'api-password',
            'services.oscbr.auth_url' => 'https://oscbr.test/api/v3/oauth/token',
            'services.oscbr.product_url' => 'https://oscbr.test/api/v3/gtin',
        ]);

        Http::fake([
            'https://oscbr.test/api/v3/oauth/token' => Http::response([
                'token' => 'short-lived-token',
            ]),
            'https://oscbr.test/api/v3/gtin/7896116900029' => Http::response([
                'ean' => '7896116900029',
                'nome' => 'FEIJAO CARIOCA KICALDO T1 1KG',
                'marca' => 'KICALDO',
                'categoria' => 'Carioca',
                'ncm' => 7133399,
                'link_foto' => 'https://oscbr.test/api/v3/gtin/7896116900029/image',
            ]),
        ]);

        $provider = new OscbrProductDataProvider();
        $firstResult = $provider->getProductData('7896116900029');
        $secondResult = $provider->getProductData('7896116900029');

        $this->assertSame('FEIJAO CARIOCA KICALDO T1 1KG', $firstResult['name']);
        $this->assertSame('07133399', $firstResult['ncm']);
        $this->assertStringNotContainsString('07133399', $firstResult['search_description']);
        $this->assertSame(
            'Bearer short-lived-token',
            $firstResult['_image_headers']['Authorization'],
        );
        $this->assertSame(ProductRefinementStatus::OscbrValidated->value, $secondResult['refined']);
        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://oscbr.test/api/v3/oauth/token'
            && $request->method() === 'POST'
            && $request->hasHeader(
                'Authorization',
                'Basic '.base64_encode('api-user:api-password'),
            ));
        Http::assertSent(fn (Request $request) => $request->url() === 'https://oscbr.test/api/v3/gtin/7896116900029'
            && $request->hasHeader('Authorization', 'Bearer short-lived-token'));
    }

    private function configureUsagePolicy(
        $provider,
        string $key,
        int $batchSize = 10,
        int $recurrenceMinutes = 1,
        int $dailyLimit = 50,
    ): void {
        $provider->shouldReceive('key')->andReturn($key);
        $provider->shouldReceive('batchSize')->andReturn($batchSize);
        $provider->shouldReceive('recurrenceMinutes')->andReturn($recurrenceMinutes);
        $provider->shouldReceive('dailyLimit')->andReturn($dailyLimit);
    }
}
