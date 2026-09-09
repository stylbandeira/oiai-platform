<?php

namespace Tests\Feature\Integration\ProductCategoryController;

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider usersProvider
     */
    public function test_all_user_types_can_view_categories(string $state, string $url): void
    {
        $user = User::factory()->{$state}()->create();
        ProductCategory::factory()->create([
            'name' => 'mercearia',
            'description' => 'Produtos de mercearia',
        ]);

        $response = $this->actingAs($user)
            ->getJson($url);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Mercearia',
                'description' => 'Produtos de mercearia',
            ]);
    }

    public function test_categories_return_using_category_resource(): void
    {
        $client = User::factory()->client()->create();
        ProductCategory::factory()->create([
            'name' => 'bebidas',
            'description' => 'Bebidas em geral',
        ]);

        $response = $this->actingAs($client)
            ->getJson('/api/categories');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'description',
                ]],
            ])
            ->assertJsonPath('data.0.name', 'Bebidas')
            ->assertJsonPath('data.0.description', 'Bebidas em geral');
    }

    public function usersProvider(): array
    {
        return [
            ['admin', '/api/admin/categories'],
            ['client', '/api/categories'],
            ['company', '/api/categories'],
        ];
    }
}
