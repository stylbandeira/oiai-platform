<?php

namespace Tests\Feature\Integration\UnityController;

use App\Models\Unity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnityIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidFiltersProvider
     */
    public function test_request_is_validated(array $query, string $field): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/unities?' . http_build_query($query));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_returns_paginated_unities(): void
    {
        $admin = User::factory()->admin()->create();
        Unity::factory()->create([
            'name' => 'grama',
            'abbreviation' => 'g',
        ]);
        Unity::factory()->create([
            'name' => 'quilograma',
            'abbreviation' => 'kg',
        ]);
        Unity::factory()->create([
            'name' => 'litro',
            'abbreviation' => 'l',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/unities?per_page=2');

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_returns_unities_based_on_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $unity = Unity::factory()->create([
            'name' => 'quilograma',
            'abbreviation' => 'kg',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/unities?search=quilo');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'abbreviation',
                ]],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.name', 'quilograma')
            ->assertJsonPath('data.0.abbreviation', 'kg');
    }

    public function invalidFiltersProvider(): array
    {
        return [
            [['search' => [123]], 'search'],
            [['per_page' => 0], 'per_page'],
            [['per_page' => 'abc'], 'per_page'],
        ];
    }
}
