<?php

namespace Tests\Feature\Integration\InvoiceController;

use App\Models\Invoice;
use App\Models\User;
use App\Services\NFCeScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceProcessInvoiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidPayloadsProvider
     */
    public function test_validation(array $payload, string $field): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/process', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_process_invoice_uses_scraper_mock(): void
    {
        $client = User::factory()->client()->create();

        $this->mockSuccessfulScraper('QR-CODE-123', 'NFCe123456789');

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/process', [
                'qr_code_data' => 'QR-CODE-123',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'NFCe processada com sucesso',
            ]);
    }

    public function test_invoice_is_created_and_returned_in_response(): void
    {
        $client = User::factory()->client()->create();

        $this->mockSuccessfulScraper('QR-CODE-456', 'NFCe987654321');

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/process', [
                'qr_code_data' => 'QR-CODE-456',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('invoice.access_key', 'NFCe987654321')
            ->assertJsonPath('invoice.user_id', $client->id)
            ->assertJsonPath('invoice.pending', true);

        $this->assertDatabaseHas('invoice', [
            'user_id' => $client->id,
            'access_key' => 'NFCe987654321',
            'pending' => true,
        ]);
    }

    public function test_scraper_error_returns_bad_request(): void
    {
        $client = User::factory()->client()->create();

        $this->mock(NFCeScraperService::class, function ($mock) {
            $mock->shouldReceive('scrapeFromQRCode')
                ->once()
                ->with('INVALID-QR')
                ->andReturn([
                    'status' => 'error',
                    'error' => 'QR inválido',
                ]);
        });

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/process', [
                'qr_code_data' => 'INVALID-QR',
            ]);

        $response
            ->assertStatus(400)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'QR inválido',
                'qr_data' => 'INVALID-QR',
            ]);

        $this->assertDatabaseCount('invoice', 0);
    }

    public function test_sp_nfce_access_key_uses_state_qr_code_route(): void
    {
        $client = User::factory()->client()->create();
        $accessKey = '35260747508411094703651070005749261615098569';

        $this->mock(NFCeScraperService::class, function ($mock) use ($accessKey) {
            $mock->shouldReceive('scrapeFromQRCode')
                ->once()
                ->with($accessKey)
                ->andReturn([
                    'status' => 'success',
                    'data' => [
                        'chave_acesso' => $accessKey,
                        'dados_nota' => ['data_emissao' => '20/07/2026 09:34:30'],
                        'protocolo' => ['data_recebimento' => '20/07/2026 09:34:33'],
                        'emitente' => [],
                        'produtos' => [],
                    ],
                ]);
        });

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/process', ['invoice_code' => $accessKey]);

        $response
            ->assertOk()
            ->assertJsonPath('invoice.access_key', 'NFCe' . $accessKey);
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [[], 'qr_code_data'],
            [['qr_code_data' => ['not-string']], 'qr_code_data'],
            [['invoice_code' => ['not-string']], 'invoice_code'],
        ];
    }

    private function mockSuccessfulScraper(string $qrCode, string $accessKey): void
    {
        $this->mock(NFCeScraperService::class, function ($mock) use ($qrCode, $accessKey) {
            $mock->shouldReceive('scrapeFromQRCode')
                ->once()
                ->with($qrCode)
                ->andReturn([
                    'status' => 'success',
                    'data' => [
                        'chave_acesso' => $accessKey,
                        'protocolo' => [
                            'data_recebimento' => '01/06/2026 12:00:00',
                        ],
                        'produtos' => [],
                    ],
                ]);
        });
    }
}
