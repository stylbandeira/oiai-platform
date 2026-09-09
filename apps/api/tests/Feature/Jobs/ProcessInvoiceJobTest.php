<?php

namespace Tests\Feature\Jobs;

use App\Enums\ProductQuantitySource;
use App\Jobs\ProcessInvoiceJob;
use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Unity;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProcessInvoiceJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_price_is_recorded_without_overwriting_existing_averages(): void
    {
        $user = User::factory()->client()->create();
        Unity::factory()->create(['abbreviation' => 'un']);
        $company = Company::factory()->create([
            'cnpj' => '45543915100640',
            'ie' => '123456789',
        ]);
        $product = Product::factory()->create([
            'ean' => '7804622380587',
            'average_price' => 27.49,
        ]);
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => 27.99,
        ]);
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'access_key' => 'invoice-average-price-test',
            'receipt_data' => now()->toDateString(),
            'invoice_data' => json_encode([
                'emitente' => [
                    'cnpj' => $company->cnpj,
                    'ie' => $company->ie,
                    'razao_social' => $company->name,
                    'endereco' => 'Rua Teste',
                    'numero' => '100',
                    'bairro' => 'Centro',
                    'municipio' => 'Petrolina',
                    'uf' => 'PE',
                    'cep' => '56300000',
                ],
                'produtos' => [[
                    'ean' => $product->ean,
                    'codigo' => $product->sku,
                    'descricao' => $product->name,
                    'unidade' => 'UN',
                    'valor_unitario' => 25.99,
                ]],
                'dados_nota' => [
                    'data_emissao' => now()->format('d/m/Y H:i:sP'),
                ],
            ]),
            'pending' => true,
        ]);

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('createProductInsertionEvent')->once();

        (new ProcessInvoiceJob($notificationService))->processInvoice($invoice);

        $this->assertEquals(27.49, $product->fresh()->average_price);
        $this->assertEquals(27.99, $companyProduct->fresh()->average_price);
        $this->assertDatabaseHas('user_added_products', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'product_id' => $product->id,
            'company_product_id' => $companyProduct->id,
            'price' => 25.99,
        ]);
    }

    public function test_invoice_product_uses_registered_unity_dimension_and_default_extraction_confidence(): void
    {
        $user = User::factory()->client()->create();
        $unity = Unity::factory()->create([
            'abbreviation' => 'kg',
            'name' => 'quilograma',
            'dimension' => 'mass',
            'convertion_factor' => 1000,
        ]);
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'access_key' => 'invoice-product-quantity-metadata-test',
            'receipt_data' => now()->toDateString(),
            'invoice_data' => json_encode([
                'emitente' => [
                    'cnpj' => '24333585000120',
                    'ie' => '014687747',
                    'razao_social' => 'Mercado Teste',
                    'endereco' => 'Rua Teste',
                    'numero' => '100',
                    'bairro' => 'Centro',
                    'municipio' => 'Petrolina',
                    'uf' => 'PE',
                    'cep' => '56300000',
                ],
                'produtos' => [[
                    'ean' => '7891234567890',
                    'codigo' => 'PRODUTO-KG',
                    'descricao' => 'Produto vendido por peso',
                    'unidade' => 'KG',
                    'quantidade' => 2.5,
                    'valor_unitario' => 10.00,
                ]],
                'dados_nota' => [
                    'data_emissao' => now()->format('d/m/Y H:i:sP'),
                ],
            ]),
            'pending' => true,
        ]);

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('createProductInsertionEvent')->once();

        (new ProcessInvoiceJob($notificationService))->processInvoice($invoice);

        $product = Product::where('ean', '7891234567890')->firstOrFail();
        $this->assertSame($unity->id, $product->unit_id);
        $this->assertSame($unity->dimension, $product->quantity_dimension);
        $this->assertSame(ProductQuantitySource::DefaultExtraction, $product->quantity_source);
        $this->assertSame('default_extraction', $product->getRawOriginal('quantity_source'));
        $this->assertSame(0.90, $product->quantity_confidence);
    }
}
