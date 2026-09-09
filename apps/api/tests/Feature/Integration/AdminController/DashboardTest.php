<?php

namespace Tests\Feature\Integration\AdminController;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }

    public function test_forbidden_for_client(): void
    {
        $client = User::factory()->create([
            'type' => 'client',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($client)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_forbidden_for_company(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($company)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_assert_returned_values()
    {
        $admin = User::factory()->create([
            'type' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/dashboard');

        $response->assertJsonStructure([
            'systemStats',
            'topUsers',
            'topStores',
            'topProducts',
        ])
            ->assertStatus(200);
    }
}
