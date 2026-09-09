<?php

namespace Tests\Feature\Integration\InvoiceController;

use App\Models\User;
use App\Services\NFCeXMLParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceProcessXMLTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_xml_uses_xml_parser_service(): void
    {
        $client = User::factory()->client()->create();

        $this->mock(NFCeXMLParserService::class, function ($mock) {
            $mock->shouldReceive('parseXML')
                ->once()
                ->with('<xml>conteudo</xml>')
                ->andReturn([
                    'status' => 'success',
                    'data' => [],
                ]);
        });

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/processXML', [
                'xml_content' => '<xml>conteudo</xml>',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'message' => 'XML processado com sucesso',
            ]);
    }

    /**
     * @dataProvider invalidPayloadsProvider
     */
    public function test_validation_returns_errors_and_status_for_invalid_data(array $payload, string $field): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/processXML', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_parser_error_returns_bad_request(): void
    {
        $client = User::factory()->client()->create();

        $this->mock(NFCeXMLParserService::class, function ($mock) {
            $mock->shouldReceive('parseXML')
                ->once()
                ->andReturn([
                    'status' => 'error',
                    'error' => 'XML inválido',
                ]);
        });

        $response = $this->actingAs($client)
            ->postJson('/api/invoice/processXML', [
                'xml_content' => '<xml>invalid</xml>',
            ]);

        $response
            ->assertStatus(400)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Erro ao tentar capturar dados da NFCe',
            ]);
    }

    public function invalidPayloadsProvider(): array
    {
        return [
            [[], 'xml_content'],
            [['xml_content' => ['not-string']], 'xml_content'],
        ];
    }
}
