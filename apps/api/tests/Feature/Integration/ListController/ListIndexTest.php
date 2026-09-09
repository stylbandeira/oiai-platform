<?php

namespace Tests\Feature\Integration\ListController;

use App\Models\ItensList;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_are_paginated(): void
    {
        $user = User::factory()->client()->create();
        $this->createList($user, 'Lista 1');
        $this->createList($user, 'Lista 2');
        $this->createList($user, 'Lista 3');

        $response = $this->actingAs($user)
            ->getJson('/api/lists?per_page=2');

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_resource_format(): void
    {
        $user = User::factory()->client()->create();
        $product = Product::factory()->create();
        $list = $this->createList($user, 'Lista do Cliente');
        $list->products()->attach($product->id, ['quantity' => 2]);

        $response = $this->actingAs($user)
            ->getJson('/api/lists');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'optimized',
                    'user_id',
                    'name',
                    'favorite',
                    'status',
                    'total',
                    'created_at',
                    'products',
                    'productsQuantity',
                ]],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.productsQuantity', 1);
    }

    public function test_lists_are_ordered_by_latest_and_products_are_eager_loaded(): void
    {
        $user = User::factory()->client()->create();
        $oldList = $this->createList($user, 'Lista Antiga', now()->subDay());
        $newList = $this->createList($user, 'Lista Nova', now());
        $product = Product::factory()->create(['name' => 'Arroz']);
        $newList->products()->attach($product->id, ['quantity' => 3]);

        $response = $this->actingAs($user)
            ->getJson('/api/lists');

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $newList->id)
            ->assertJsonPath('data.1.id', $oldList->id)
            ->assertJsonPath('data.0.products.0.name', 'Arroz');
    }

    private function createList(User $user, string $name, $createdAt = null): ItensList
    {
        return ItensList::create([
            'user_id' => $user->id,
            'name' => $name,
            'favorite' => false,
            'total' => 0,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }
}
