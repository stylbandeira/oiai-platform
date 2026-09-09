<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_ownership_request_activated_creates_event_notification(): void
    {
        $user = User::factory()->company()->create();
        $company = Company::factory()->create([
            'name' => 'Empresa Teste',
        ]);

        app(NotificationService::class)->userOwnershipRequestActivated($user, $company);

        $this->assertDatabaseHas('event', [
            'user_id' => $user->id,
            'target_type' => 'company',
            'title' => 'company_ownership_active',
            'where' => '',
            'type' => 'company',
            'points' => 0,
            'link' => 'company_ownership_active',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);
    }
}
