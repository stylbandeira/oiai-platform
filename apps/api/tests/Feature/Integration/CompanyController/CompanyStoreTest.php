<?php

namespace Tests\Feature\Integration\CompanyController;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized(): void
    {
        $response = $this->postJson('/api/companies', $this->validPayload());

        $response->assertStatus(401);
    }

    public function test_admin_can_create_a_company(): void
    {
        $admin = User::factory()->create([
            'type' => 'admin',
        ]);

        $payload = $this->validPayload([
            'name' => 'Mercado Central',
            'cnpj' => '12345678000190',
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/companies', $payload);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Mercado Central',
                'cnpj' => '12345678000190',
            ]);

        $this->assertDatabaseHas('company', [
            'name' => 'Mercado Central',
            'cnpj' => '12345678000190',
            'email' => $payload['email'],
        ]);
    }

    public function test_company_user_can_create_a_company(): void
    {
        $companyUser = User::factory()->create([
            'type' => 'company',
            'email_verified_at' => now(),
        ]);

        $payload = $this->validPayload([
            'name' => 'Padaria Primavera',
            'cnpj' => '98765432000110',
        ]);

        $response = $this->actingAs($companyUser)
            ->postJson('/api/companies', $payload);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Padaria Primavera',
                'cnpj' => '98765432000110',
            ]);

        $this->assertDatabaseHas('company', [
            'name' => 'Padaria Primavera',
            'cnpj' => '98765432000110',
        ]);
    }

    public function test_client_cant_create_a_company(): void
    {
        $client = User::factory()->create([
            'type' => 'client',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($client)
            ->postJson('/api/companies', $this->validPayload());

        $response->assertStatus(403);

        $this->assertDatabaseCount('company', 0);
    }

    /**
     * @dataProvider invalidFieldsProvider
     */
    public function test_invalid_fields(string $field, mixed $value): void
    {
        $admin = User::factory()->create([
            'type' => 'admin',
        ]);

        $payload = $this->validPayload();
        $payload[$field] = $value;

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/companies', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_cnpj_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'type' => 'admin',
        ]);

        Company::factory()->create([
            'cnpj' => '12345678000190',
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/companies', $this->validPayload([
                'cnpj' => '12345678000190',
            ]));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('cnpj');
    }

    public function invalidFieldsProvider(): array
    {
        return [
            ['name', null],
            ['name', 123],
            ['cnpj', null],
            ['cnpj', 123],
            ['img', 'not-an-image'],
            ['website', 123],
            ['email', 'invalid-email'],
            ['status', 123],
            ['phone', 123],
            ['description', 123],
            ['raw_address', 123],
        ];
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Empresa Teste',
            'cnpj' => '11222333000181',
            'website' => 'https://empresa.test',
            'email' => 'contato@empresa.test',
            'status' => 'active',
            'phone' => '81999990000',
            'description' => 'Empresa criada em teste de integracao.',
            'raw_address' => 'Rua dos Testes, 123',
        ], $overrides);
    }
}
