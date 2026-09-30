<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessInvoiceJob;
use App\Models\Invoice;
use App\Models\Unity;
use App\Models\User;
use App\Models\UserAddedProducts;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProcessInvoiceDateTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('invoiceDateFields')]
    public function test_uses_issue_date_for_all_products_from_xml_or_legacy_payload(string $dateField): void
    {
        $user = User::factory()->client()->create();
        Unity::factory()->create(['abbreviation' => 'un']);
        $invoice = $this->makeInvoice($user, [
            $dateField => ['data_emissao' => '19/08/2026 09:24:18'],
        ]);

        /** @var NotificationService&MockInterface $notificationService */
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('createProductInsertionEvent')->once();

        (new ProcessInvoiceJob($notificationService))->processInvoice($invoice);

        $this->assertSame(0, (int) $invoice->fresh()->pending);
        $purchases = UserAddedProducts::query()->where('user_id', $user->id)->get();
        $this->assertCount(2, $purchases);
        foreach ($purchases as $purchase) {
            $this->assertSame('2026-08-19 09:24:18', Carbon::parse($purchase->purchase_date)->format('Y-m-d H:i:s'));
        }
    }

    public function test_missing_issue_date_warns_once_and_uses_receipt_date(): void
    {
        $user = User::factory()->client()->create();
        Unity::factory()->create(['abbreviation' => 'un']);
        $invoice = $this->makeInvoice($user, []);
        $log = Log::spy();

        /** @var NotificationService&MockInterface $notificationService */
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('createProductInsertionEvent')->once();

        (new ProcessInvoiceJob($notificationService))->processInvoice($invoice);

        $log->shouldHaveReceived('warning')->once();
        $purchases = UserAddedProducts::query()->where('user_id', $user->id)->get();
        $this->assertCount(2, $purchases);
        foreach ($purchases as $purchase) {
            $this->assertSame('2026-09-30 00:00:00', Carbon::parse($purchase->purchase_date)->format('Y-m-d H:i:s'));
        }
    }

    public static function invoiceDateFields(): array
    {
        return [['nota'], ['dados_nota']];
    }

    private function makeInvoice(User $user, array $dateFields): Invoice
    {
        return Invoice::create([
            'user_id' => $user->id,
            'access_key' => 'invoice-date-test-'.$user->id,
            'receipt_data' => '2026-09-30',
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
                'produtos' => [
                    [
                        'ean' => '7891234567890',
                        'codigo' => 'ITEM-1',
                        'descricao' => 'Produto um',
                        'unidade' => 'UN',
                        'valor_unitario' => 10.00,
                    ],
                    [
                        'ean' => '7891234567891',
                        'codigo' => 'ITEM-2',
                        'descricao' => 'Produto dois',
                        'unidade' => 'UN',
                        'valor_unitario' => 20.00,
                    ],
                ],
                ...$dateFields,
            ]),
            'pending' => true,
        ]);
    }
}
