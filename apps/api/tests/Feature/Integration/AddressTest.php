<?php

namespace Tests\Feature\Integration;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized(): void
    {
        $response = $this->postJson('/api/addresses', [
            'country' => 'Austrália',
            'area' => 'Sei Lá',
            'city' => 'Montread',
            'street' => 'Cicada Street',
            'number' => '2980',
            'complement' => 'A',
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_create_an_address(): void
    {
        $admin = User::factory()->create([
            'type' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/addresses', [
                'country' => 'Austrália',
                'area' => 'Sei Lá',
                'city' => 'Montread',
                'street' => 'Cicada Street',
                'number' => '2980',
                'complement' => 'A',
            ]);

        $response->assertStatus(200);
    }

    public function test_client_cant_create_an_address(): void
    {
        $client = User::factory()->create([
            'type' => 'client',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($client)
            ->postJson('/api/addresses', [
                'country' => 'Austrália',
                'area' => 'Sei Lá',
                'city' => 'Montread',
                'street' => 'Cicada Street',
                'number' => '2980',
                'complement' => 'A',
            ]);

        $response->assertStatus(403);
    }

    public function test_company_can_create_an_address(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($company)
            ->postJson('/api/addresses', [
                'country' => 'Austrália',
                'area' => 'Sei Lá',
                'city' => 'Montread',
                'street' => 'Cicada Street',
                'number' => '2980',
                'complement' => 'A',
            ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider invalidFieldsProvider
     *
     * @return void
     */
    public function test_invalid_fields(string $field, mixed $value): void
    {
        $admin = User::factory()->create([
            'type' => 'admin',
            'email_verified_at' => now(),
        ]);

        $data = Address::factory()->make()->toArray();
        $data[$field] = $value;

        $response = $this->actingAs($admin)
            ->postJson('/api/addresses', $data);

        $response->assertJsonValidationErrorFor($field);
    }

    public function invalidFieldsProvider()
    {
        return [
            ['country', null],
            ['country', 123],
            ['area', null],
            ['area', 123],
            ['city', null],
            ['city', 123],
            ['street', ''],
            ['street', null],
            ['street', 123],
            ['number', ''],
            ['number', null],
            ['number', 123],
            ['complement', 123],
        ];
    }
}
