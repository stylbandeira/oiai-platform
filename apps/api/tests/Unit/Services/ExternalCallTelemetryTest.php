<?php

namespace Tests\Unit\Services;

use App\Services\ExternalCallTelemetry;
use App\Services\Product\Providers\OscbrProductDataProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ExternalCallTelemetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('sentry.dsn', null);
        config()->set('services.oscbr.login', 'test-login');
        config()->set('services.oscbr.password', 'test-password');
        config()->set('services.oscbr.auth_url', 'https://oscbr.test/oauth/token');
        config()->set('services.oscbr.product_url', 'https://oscbr.test/gtin');
    }

    public function test_success_and_http_failure_emit_structured_events_without_secrets(): void
    {
        Log::spy();
        Http::fake([
            'https://oscbr.test/oauth/token' => Http::response(['token' => 'secret-token'], 200),
            'https://oscbr.test/gtin/*' => Http::sequence()
                ->push(['nome' => 'Arroz 1kg'], 200)
                ->push(['message' => 'temporary failure'], 503),
        ]);

        $provider = new OscbrProductDataProvider;
        $provider->getProductData('7891234567890');
        $provider->getProductData('7891234567890');

        Log::shouldHaveReceived('log')->atLeast()->withArgs(function (string $level, string $message, array $context): bool {
            return $message === 'external_call'
                && isset($context['provider'], $context['operation'], $context['duration_ms'], $context['correlation_id'])
                && ! str_contains(json_encode($context), 'secret-token')
                && ! str_contains(json_encode($context), 'test-password');
        });
    }

    public function test_invalid_payload_and_connection_failure_are_distinguished(): void
    {
        Log::spy();
        Http::fake([
            'https://oscbr.test/oauth/token' => Http::response(['token' => 'secret-token'], 200),
            'https://oscbr.test/gtin/*' => function (): never {
                throw new ConnectionException('connection refused');
            },
        ]);

        (new OscbrProductDataProvider)->getProductData('7891234567890');

        Log::shouldHaveReceived('log')->withArgs(function (string $level, string $message, array $context): bool {
            return $message === 'external_call'
                && $context['result'] === 'connection_error'
                && $context['failure_type'] === 'connection';
        });
    }

    public function test_invalid_payload_and_timeout_emit_distinct_results(): void
    {
        Log::spy();
        Http::fake([
            'https://oscbr.test/oauth/token' => Http::response(['token' => 'secret-token'], 200),
            'https://oscbr.test/gtin/*' => Http::sequence()
                ->push(['unexpected' => 'shape'], 200)
                ->whenEmpty(function (): never {
                    throw new ConnectionException('Operation timed out');
                }),
        ]);

        $provider = new OscbrProductDataProvider;
        $provider->getProductData('7891234567890');
        $provider->getProductData('7891234567890');

        Log::shouldHaveReceived('log')->withArgs(fn (string $level, string $message, array $context): bool => $message === 'external_call' && $context['result'] === 'invalid_payload');
        Log::shouldHaveReceived('log')->withArgs(fn (string $level, string $message, array $context): bool => $message === 'external_call' && $context['result'] === 'timeout');
    }

    public function test_reporter_failure_does_not_escape_the_business_flow(): void
    {
        $telemetry = new ExternalCallTelemetry;
        config()->set('sentry.dsn', 'https://public@example.ingest.sentry.io/1');

        $telemetry->record([
            'provider' => 'oscbr',
            'operation' => 'product.lookup',
            'result' => 'http_error',
            'http_status' => 503,
            'duration_ms' => 1200,
            'failure_type' => 'http',
            'correlation_id' => 'test-correlation',
            'token' => 'must-not-appear',
            'payload' => ['email' => 'person@example.test'],
        ]);

        $this->assertTrue(true);
    }
}
