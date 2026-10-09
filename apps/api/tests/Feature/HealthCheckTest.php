<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_is_public_and_returns_ok(): void
    {
        $this->fakeDependencies();

        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'checks' => [
                    'application' => 'ok',
                    'database' => 'ok',
                    'queue' => 'ok',
                    'meilisearch' => 'ok',
                ],
            ]);
    }

    public function test_database_failure_returns_503_without_exposing_the_exception(): void
    {
        $this->fakeDependencies(databaseFails: true);

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.application', 'ok')
            ->assertJsonPath('checks.database', 'error')
            ->assertJsonPath('checks.queue', 'ok')
            ->assertJsonPath('checks.meilisearch', 'ok')
            ->assertDontSee('database unavailable');
    }

    public function test_queue_failure_returns_503(): void
    {
        $this->fakeDependencies(queueFails: true);

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('checks.queue', 'error')
            ->assertJsonPath('checks.database', 'ok');
    }

    public function test_meilisearch_failure_returns_503(): void
    {
        $this->fakeDependencies(meilisearchFails: true);

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('checks.meilisearch', 'error')
            ->assertJsonPath('checks.queue', 'ok');
    }

    private function fakeDependencies(
        bool $databaseFails = false,
        bool $queueFails = false,
        bool $meilisearchFails = false,
    ): void {
        config()->set('scout.meilisearch.host', 'http://search:7700');

        $database = DB::shouldReceive('select')->once()->with('SELECT 1');
        $databaseFails ? $database->andThrow(new RuntimeException('database unavailable')) : $database->andReturn([1]);

        $queue = Queue::shouldReceive('size')->once();
        $queueFails ? $queue->andThrow(new RuntimeException('queue unavailable')) : $queue->andReturn(0);

        Http::fake([
            'http://search:7700/health' => $meilisearchFails
                ? Http::response(['status' => 'unavailable'], 503)
                : Http::response(['status' => 'available']),
        ]);
    }
}
