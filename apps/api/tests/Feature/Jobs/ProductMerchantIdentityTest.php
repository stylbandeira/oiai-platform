<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessInvoiceJob;
use App\Models\CompanyProductCode;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductReviewSuggestion;
use App\Models\Unity;
use App\Models\User;
use App\Models\UserAddedProducts;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductMerchantIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_local_code_in_different_markets_does_not_merge_products(): void
    {
        $user = User::factory()->client()->create();
        Unity::factory()->create(['abbreviation' => 'un']);
        $notifications = Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('createProductInsertionEvent')->times(3);
        $job = new ProcessInvoiceJob($notifications);

        $this->process($job, $user, '24333585000120', '1', '123', 'SEM GTIN', 'Banana');
        $this->process($job, $user, '24333585000121', '2', '123', 'SEM GTIN', 'Banana');

        $this->assertSame(2, Product::query()->count());
        $this->assertSame(2, CompanyProductCode::query()->where('code', '123')->count());
        $this->assertSame(1, ProductReviewSuggestion::query()->where('type', 'equivalence')->count());

        $this->process($job, $user, '24333585000120', '3', '123', 'SEM GTIN', 'Banana');
        $this->assertSame(2, Product::query()->count());
    }

    public function test_valid_gtin_matches_across_markets_without_overwriting_global_sku(): void
    {
        $user = User::factory()->client()->create();
        Unity::factory()->create(['abbreviation' => 'un']);
        $notifications = Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('createProductInsertionEvent')->twice();
        $job = new ProcessInvoiceJob($notifications);

        $this->process($job, $user, '24333585000120', '1', 'A-1', '7891000100103', 'Achocolatado');
        $sku = Product::query()->firstOrFail()->sku;
        $this->process($job, $user, '24333585000121', '2', 'B-9', '7891000100103', 'Achocolatado');

        $this->assertSame(1, Product::query()->count());
        $this->assertSame($sku, Product::query()->firstOrFail()->sku);
        $this->assertSame(2, CompanyProductCode::query()->count());
    }

    public function test_approved_equivalence_changes_future_matches_but_preserves_purchase_history(): void
    {
        $user = User::factory()->client()->create();
        $admin = User::factory()->admin()->create();
        Unity::factory()->create(['abbreviation' => 'un']);
        $notifications = Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('createProductInsertionEvent')->times(3);
        $job = new ProcessInvoiceJob($notifications);

        $this->process($job, $user, '24333585000120', '1', 'A', 'SEM GTIN', 'Banana');
        $this->process($job, $user, '24333585000121', '2', 'B', 'SEM GTIN', 'Banana');
        $suggestion = ProductReviewSuggestion::query()->where('type', 'equivalence')->firstOrFail();
        $originalPurchases = UserAddedProducts::query()->where('product_id', $suggestion->product_id)->count();

        $this->actingAs($admin)->postJson(
            "/api/admin/product-review-suggestions/{$suggestion->id}/review",
            ['decision' => 'approved'],
        )->assertOk();

        $this->assertFalse((bool) $suggestion->product->fresh()->validated);
        $this->assertSame('approved', $suggestion->fresh()->status);
        $this->assertSame(
            $suggestion->suggested_product_id,
            CompanyProductCode::query()->where('code', 'B')->firstOrFail()->product_id,
        );
        $this->assertSame($originalPurchases, UserAddedProducts::query()->where('product_id', $suggestion->product_id)->count());

        $this->process($job, $user, '24333585000121', '3', 'B', 'SEM GTIN', 'Banana');
        $this->assertSame($originalPurchases, UserAddedProducts::query()->where('product_id', $suggestion->product_id)->count());
        $this->assertSame(2, UserAddedProducts::query()->where('product_id', $suggestion->suggested_product_id)->count());
    }

    private function process(
        ProcessInvoiceJob $job,
        User $user,
        string $cnpj,
        string $accessKey,
        string $code,
        string $ean,
        string $name,
    ): void {
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'access_key' => 'merchant-identity-'.$accessKey,
            'receipt_data' => now()->toDateString(),
            'invoice_data' => json_encode([
                'emitente' => [
                    'cnpj' => $cnpj,
                    'ie' => '123456789',
                    'razao_social' => 'Mercado '.$cnpj,
                    'endereco' => 'Rua Teste',
                    'numero' => '100',
                    'bairro' => 'Centro',
                    'municipio' => 'Petrolina',
                    'uf' => 'PE',
                ],
                'produtos' => [[
                    'ean' => $ean,
                    'codigo' => $code,
                    'descricao' => $name,
                    'unidade' => 'UN',
                    'valor_unitario' => 10,
                ]],
                'dados_nota' => ['data_emissao' => now()->format('d/m/Y H:i:sP')],
            ]),
            'pending' => true,
        ]);

        $job->processInvoice($invoice);
    }
}
