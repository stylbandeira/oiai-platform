<?php

namespace Tests\Feature\Integration\EventController;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCheckAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_all_marks_only_authenticated_users_notifications_as_checked(): void
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->client()->create();
        $firstEvent = $this->createEvent(['user_id' => $user->id]);
        $secondEvent = $this->createEvent(['user_id' => $user->id, 'checked' => true]);
        $otherEvent = $this->createEvent(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/events/check-all', ['notifications' => [['id' => $otherEvent->id]]]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Todas as notificações marcadas como lidas.',
            ]);

        $this->assertDatabaseHas('event', [
            'id' => $firstEvent->id,
            'checked' => true,
        ]);
        $this->assertDatabaseHas('event', [
            'id' => $secondEvent->id,
            'checked' => true,
        ]);
        $this->assertDatabaseHas('event', [
            'id' => $otherEvent->id,
            'checked' => false,
        ]);
    }

    public function test_check_all_is_idempotent_and_accepts_empty_payload(): void
    {
        $user = User::factory()->client()->create();
        $event = $this->createEvent(['user_id' => $user->id]);

        $this->actingAs($user)->postJson('/api/events/check-all')->assertOk();
        $this->actingAs($user)->postJson('/api/events/check-all')->assertOk();

        $this->assertDatabaseHas('event', ['id' => $event->id, 'checked' => true]);
    }

    private function createEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'title' => Event::TYPE_PRODUCT_INSERT,
            'description' => 'Evento de teste.',
            'where' => 'Teste',
            'type' => 'client',
            'points' => 0,
            'link' => Event::TYPE_PRODUCT_INSERT,
            'checked' => false,
            'target_type' => 'user',
        ], $overrides));
    }
}
