<?php

namespace Tests\Feature\Integration\CompanyOwnersController;

use App\Models\CompanyOwners;
use App\Models\Event;
use App\Models\User;
use App\Repositories\EventRepository;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CompanyOwnersStoreCompanyAndRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider forbiddenUsersProvider
     */
    public function test_only_company_and_admin_users_can_store_company_and_request_access(string $userFactoryState): void
    {
        $user = User::factory()->{$userFactoryState}()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/companies/request-with-new-company', $this->validPayload());

        $response
            ->assertStatus(403);
    }

    /**
     * @dataProvider invalidFieldsProvider
     */
    public function test_validation_returns_correct_status_and_error_messages(string $field, mixed $value): void
    {
        $companyUser = User::factory()->company()->create();
        $payload = $this->validPayload();
        $payload[$field] = $value;

        $response = $this->actingAs($companyUser)
            ->postJson('/api/companies/request-with-new-company', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_store_company_and_request_creates_company_owner_and_event(): void
    {
        $companyUser = User::factory()->company()->create([
            'name' => 'Usuario Empresa',
        ]);

        $response = $this->actingAs($companyUser)
            ->postJson('/api/companies/request-with-new-company', $this->validPayload([
                'name' => 'Empresa Nova',
                'cnpj' => '11222333000181',
            ]));

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Empresa cadastrada e solicitação enviada.',
            ]);

        $this->assertDatabaseHas('company', [
            'name' => 'Empresa Nova',
            'cnpj' => '11222333000181',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('company_owners', [
            'user_id' => $companyUser->id,
            'status' => CompanyOwners::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('event', [
            'user_id' => $companyUser->id,
            'title' => Event::TYPE_COMPANY_OWNER_REQUEST,
            'where' => 'Empresa Nova',
            'target_type' => 'admin',
            'entity_type' => 'user',
            'entity_id' => $companyUser->id,
        ]);
    }

    public function test_company_creation_owner_assignment_and_event_happen_in_a_single_transaction(): void
    {
        $companyUser = User::factory()->company()->create();

        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('createOwnershipRequestEvent')
                ->once()
                ->andThrow(new RuntimeException('Event creation failed.'));
        });

        $response = $this->actingAs($companyUser)
            ->postJson('/api/companies/request-with-new-company', $this->validPayload([
                'name' => 'Empresa Transacional',
                'cnpj' => '12345678000190',
            ]));

        $response->assertStatus(500);

        $this->assertDatabaseMissing('company', [
            'cnpj' => '12345678000190',
        ]);
        $this->assertDatabaseMissing('company_owners', [
            'user_id' => $companyUser->id,
        ]);
    }

    public function forbiddenUsersProvider(): array
    {
        return [
            ['client'],
        ];
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
            'cnpj' => '99888777000166',
            'website' => 'https://empresa.test',
            'email' => 'contato@empresa.test',
            'status' => 'active',
            'phone' => '81999990000',
            'description' => 'Empresa criada em teste de integracao.',
            'raw_address' => 'Rua dos Testes, 123',
        ], $overrides);
    }
}
