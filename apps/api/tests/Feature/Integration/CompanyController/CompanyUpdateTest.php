<?php

namespace Tests\Feature\Integration\CompanyController;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidFieldsProvider
     */
    public function test_invalid_fields(string $field, mixed $value): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();

        $payload = $this->validPayload();
        $payload[$field] = $value;

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/companies/' . $company->id, $payload);

        $response
            ->assertStatus(400)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_cnpj_must_be_unique_ignoring_current_company(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create([
            'cnpj' => '11222333000181',
        ]);
        Company::factory()->create([
            'cnpj' => '12345678000190',
        ]);

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/companies/' . $company->id, $this->validPayload([
                'cnpj' => '12345678000190',
            ]));

        $response
            ->assertStatus(400)
            ->assertJsonValidationErrorFor('cnpj');
    }

    public function test_admin_can_update_company_and_changes_reflect_on_database(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create([
            'name' => 'Empresa Antiga',
            'cnpj' => '11222333000181',
            'email' => 'antigo@empresa.test',
        ]);

        $payload = $this->validPayload([
            'name' => 'Empresa Atualizada',
            'cnpj' => '99888777000166',
            'email' => 'novo@empresa.test',
            'phone' => '81988887777',
            'description' => 'Descricao atualizada.',
            'raw_address' => 'Avenida Nova, 456',
        ]);

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/companies/' . $company->id, $payload);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Empresa Atualizada',
                'cnpj' => '99888777000166',
                'email' => 'novo@empresa.test',
            ]);

        $this->assertDatabaseHas('company', [
            'id' => $company->id,
            'name' => 'Empresa Atualizada',
            'cnpj' => '99888777000166',
            'email' => 'novo@empresa.test',
            'phone' => '81988887777',
            'description' => 'Descricao atualizada.',
            'raw_address' => 'Avenida Nova, 456',
        ]);
    }

    public function invalidFieldsProvider(): array
    {
        return [
            ['name', 123],
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
            'status' => Company::STATUS_ACTIVE,
            'phone' => '81999990000',
            'description' => 'Empresa usada em teste de integracao.',
            'raw_address' => 'Rua dos Testes, 123',
        ], $overrides);
    }
}
