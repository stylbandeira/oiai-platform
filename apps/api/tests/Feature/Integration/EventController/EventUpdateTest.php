<?php

namespace Tests\Feature\Integration\EventController;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider usersProvider
     */
    public function test_any_user_can_update_an_event(string $userFactoryState): void
    {
        $user = User::factory()->{$userFactoryState}()->create();
        $event = $this->createEvent([
            'title' => 'old_title',
            'checked' => false,
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/events/' . $event->id, [
                'title' => 'new_title',
                'description' => 'Descricao atualizada.',
                'checked' => true,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Evento alterado com sucesso!',
            ]);

        $this->assertDatabaseHas('event', [
            'id' => $event->id,
            'title' => 'new_title',
            'description' => 'Descricao atualizada.',
            'checked' => true,
        ]);
    }

    /**
     * @dataProvider invalidFieldsProvider
     */
    public function test_validation_returns_an_error(string $field, mixed $value): void
    {
        $user = User::factory()->client()->create();
        $event = $this->createEvent();

        $response = $this->actingAs($user)
            ->putJson('/api/events/' . $event->id, [
                $field => $value,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function usersProvider(): array
    {
        return [
            ['admin'],
            ['company'],
            ['client'],
        ];
    }

    public function invalidFieldsProvider(): array
    {
        return [
            ['title', 123],
            ['description', 123],
            ['where', 123],
            ['type', 123],
            ['points', 'not-an-integer'],
            ['link', 123],
            ['checked', 'not-a-boolean'],
            ['target_type', 123],
            ['entity_type', 123],
            ['entity_id', 'not-an-integer'],
            ['user_id', 'not-an-integer'],
        ];
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
