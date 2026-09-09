<?php

namespace Tests\Feature\Integration\ProductController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_request_is_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/products/import', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('file')
            ->assertJsonFragment([
                'message' => 'Arquivo inválido',
            ]);
    }
}
