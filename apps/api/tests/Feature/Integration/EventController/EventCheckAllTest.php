<?php

namespace Tests\Feature\Integration\EventController;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventCheckAllTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider invalidNotificationsProvider
     */
    public function test_notifications_format_is_validated(array $payload, string $field): void
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/events/check-all', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor($field);
    }

    public function test_check_all_marks_notifications_as_checked_inside_transaction(): void
    {
        $user = User::factory()->client()->create();
        $firstEvent = $this->createEvent();
        $secondEvent = $this->createEvent();

        $response = $this->actingAs($user)
            ->postJson('/api/events/check-all', [
                'notifications' => [
                    ['id' => $firstEvent->id],
                    ['id' => $secondEvent->id],
                ],
            ]);

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
    }

    public function test_check_all_starts_a_transaction_to_mark_notifications_as_checked(): void
    {
        $user = User::factory()->client()->create();

        DB::shouldReceive('transaction')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($user)
            ->postJson('/api/events/check-all', [
                'notifications' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function invalidNotificationsProvider(): array
    {
        return [
            [
                [],
                'notifications',
            ],
            [
                ['notifications' => 'not-an-array'],
                'notifications',
            ],
            [
                ['notifications' => [[]]],
                'notifications.0.id',
            ],
            [
                ['notifications' => [['id' => 'not-an-integer']]],
                'notifications.0.id',
            ],
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
