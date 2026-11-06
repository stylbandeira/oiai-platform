<?php

namespace Tests\Unit\Services;

use App\Jobs\Telemetry\SendBurasEvent;
use App\Services\BurasReporter;
use App\Services\Product\Providers\OscbrProductDataProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BurasReporterTest extends TestCase
{
    public function test_only_allowlisted_fields_are_queued(): void
    {
        config()->set('services.buras.url', 'http://buras.test');
        config()->set('services.buras.token', 'test-token');
        Queue::fake();

        app(BurasReporter::class)->record([
            'provider' => 'oscbr',
            'operation' => 'product.lookup',
            'result' => 'timeout',
            'correlation_id' => 'person@example.test',
            'duration_ms' => 12.5,
            'token' => 'secret-token-sentinel',
            'payload' => ['email' => 'person@example.test'],
            'error' => 'password=secret-token-sentinel',
        ]);

        Queue::assertPushed(SendBurasEvent::class, function (SendBurasEvent $job): bool {
            $json = json_encode($job->event);

            return $job->event['provider'] === 'oscbr'
                && $job->event['duration_ms'] === 12.5
                && preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/D', $job->event['correlation_id']) === 1
                && ! str_contains($json, 'secret-token-sentinel')
                && ! str_contains($json, 'person@example.test')
                && ! array_key_exists('payload', $job->event)
                && ! array_key_exists('error', $job->event);
        });
    }

    public function test_valid_correlation_is_preserved_across_the_queue(): void
    {
        config()->set('services.buras.url', 'http://buras.test');
        config()->set('services.buras.token', 'test-token');
        Queue::fake();
        $correlationId = '00000000-0000-0000-0000-000000000042';

        app(BurasReporter::class)->record([
            'provider' => 'oscbr',
            'operation' => 'product.lookup',
            'result' => 'success',
            'correlation_id' => $correlationId,
        ]);

        Queue::assertPushed(SendBurasEvent::class, fn (SendBurasEvent $job): bool => $job->event['correlation_id'] === $correlationId);
    }

    public function test_delivery_uses_configured_token_and_never_exposes_network_failure(): void
    {
        config()->set('services.buras.url', 'http://buras.test');
        config()->set('services.buras.token', 'test-token');
        Http::fake(['http://buras.test/v1/events' => Http::response(['accepted' => true], 202)]);
        $job = new SendBurasEvent(['event_id' => '01J00000000000000000000000']);
        $job->handle();

        Http::assertSent(fn ($request): bool => $request->url() === 'http://buras.test/v1/events'
            && $request->hasHeader('Authorization', 'Bearer test-token'));

        Http::fake(['http://buras.test/v1/events' => function (): never {
            throw new ConnectionException('secret-token-sentinel');
        }]);

        $job->handle();
        $this->assertTrue(true);
    }

    public function test_oscbr_call_reaches_buras_queue_without_credentials(): void
    {
        config()->set('services.buras.url', 'http://buras.test');
        config()->set('services.buras.token', 'test-token');
        config()->set('services.oscbr.login', 'test-login');
        config()->set('services.oscbr.password', 'secret-password-sentinel');
        config()->set('services.oscbr.auth_url', 'https://oscbr.test/oauth/token');
        config()->set('services.oscbr.product_url', 'https://oscbr.test/gtin');
        Queue::fake();
        Http::fake([
            'https://oscbr.test/oauth/token' => Http::response(['token' => 'secret-token-sentinel'], 200),
            'https://oscbr.test/gtin/*' => Http::response(['nome' => 'Arroz 1kg'], 200),
        ]);

        (new OscbrProductDataProvider)->getProductData('7891234567890');

        Queue::assertPushed(SendBurasEvent::class, function (SendBurasEvent $job): bool {
            $json = json_encode($job->event);

            return $job->event['provider'] === 'oscbr'
                && $job->event['operation'] === 'product.lookup'
                && ! str_contains($json, 'secret-password-sentinel')
                && ! str_contains($json, 'secret-token-sentinel')
                && ! str_contains($json, '7891234567890');
        });
    }

    public function test_missing_configuration_does_not_queue_events(): void
    {
        config()->set('services.buras.url', null);
        config()->set('services.buras.token', null);
        Queue::fake();
        app(BurasReporter::class)->record(['provider' => 'oscbr']);
        Queue::assertNothingPushed();
    }
}
