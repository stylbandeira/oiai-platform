<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sentry\Severity;
use Sentry\State\Scope;
use Throwable;
use function Sentry\captureMessage;
use function Sentry\configureScope;
use function Sentry\traceMetrics;

final class ExternalCallTelemetry
{
    /** @param array<string, mixed> $fields */
    public function record(array $fields): void
    {
        try {
            $event = array_merge([
                'event' => 'external_call',
                'environment' => (string) config('app.env'),
                'correlation_id' => $this->correlationId(),
                'http_status' => null,
                'duration_ms' => null,
                'failure_type' => null,
            ], $fields);
            $event['provider'] = (string) ($event['provider'] ?? 'unknown');
            $event['operation'] = (string) ($event['operation'] ?? 'unknown');
            $event['result'] = (string) ($event['result'] ?? 'failure');
            $event['duration_ms'] = isset($event['duration_ms']) ? round((float) $event['duration_ms'], 2) : null;
            $event = $this->sanitize($event);
            Log::log($this->levelFor($event['result']), 'external_call', $event);

            $this->recordMetrics($event);

            if ($this->shouldReport($event)) {
                $this->reportToSentry($event);
            }
        } catch (Throwable) {
            // Observability must never interrupt the business call.
        }
    }

    public function correlationId(?string $fallback = null): string
    {
        try {
            /** @var Request|null $request */
            $request = app()->bound('request') ? request() : null;
            $correlationId = $request?->header('X-Correlation-Id') ?: $request?->header('X-Request-Id');
            if (is_string($correlationId) && $correlationId !== '') {
                return Str::limit($correlationId, 128, '');
            }
        } catch (Throwable) {
            // Queue and console contexts may not have a request.
        }
        return $fallback ?: (string) Str::ulid();
    }

    /** @param array<string, mixed> $event */
    private function sanitize(array $event): array
    {
        foreach (['url', 'uri', 'token', 'password', 'authorization', 'body', 'payload', 'response'] as $field) {
            unset($event[$field]);
        }
        if (isset($event['exception']) && is_object($event['exception'])) {
            $event['error_class'] = $event['exception']::class;
            unset($event['exception']);
        }
        if (isset($event['error']) && is_string($event['error'])) {
            $event['error'] = Str::limit(preg_replace('/\s+/', ' ', $event['error']) ?? '', 240);
        }
        return $event;
    }

    private function levelFor(string $result): string
    {
        return match ($result) {
            'success' => 'debug',
            'not_found', 'skipped' => 'info',
            default => 'warning',
        };
    }

    /** @param array<string, mixed> $event */
    private function shouldReport(array $event): bool
    {
        return !app()->environment('testing')
            && filled(config('sentry.dsn'))
            && ($event['result'] ?? null) !== 'success'
            && ($event['result'] ?? null) !== 'not_found';
    }

    /** @param array<string, mixed> $event */
    private function reportToSentry(array $event): void
    {
        try {
            configureScope(function (Scope $scope) use ($event): void {
                $scope->setTag('provider', (string) $event['provider']);
                $scope->setTag('operation', (string) $event['operation']);
                $scope->setTag('result', (string) $event['result']);
                $scope->setTag('failure_type', (string) ($event['failure_type'] ?? 'unknown'));
                $scope->setTag('environment', (string) $event['environment']);
                $scope->setTag('correlation_id', (string) $event['correlation_id']);
                $scope->setFingerprint([
                    'external-call',
                    (string) $event['provider'],
                    (string) $event['operation'],
                    (string) $event['result'],
                    (string) ($event['failure_type'] ?? 'unknown'),
                    (string) ($event['http_status'] ?? 'none'),
                ]);
                $scope->setContext('external_call', [
                    'duration_ms' => $event['duration_ms'],
                    'http_status' => $event['http_status'],
                    'error_class' => $event['error_class'] ?? null,
                ]);
            });
            captureMessage('External integration failure', Severity::warning());
        } catch (Throwable) {
            // Sentry outages must not affect the integration.
        }
    }

    /** @param array<string, mixed> $event */
    private function recordMetrics(array $event): void
    {
        if (app()->environment('testing') || blank(config('sentry.dsn'))) {
            return;
        }

        try {
            $attributes = [
                'environment' => (string) $event['environment'],
                'provider' => (string) $event['provider'],
                'operation' => (string) $event['operation'],
                'result' => (string) $event['result'],
                'failure_type' => (string) ($event['failure_type'] ?? 'none'),
            ];
            traceMetrics()->count('external.calls', 1, $attributes);
            traceMetrics()->distribution('external.duration_ms', (float) ($event['duration_ms'] ?? 0), $attributes);

            if (in_array($event['result'], ['timeout', 'connection_error'], true)) {
                traceMetrics()->count('external.timeouts', 1, $attributes);
            }
        } catch (Throwable) {
            // Metrics are best effort and must not affect the integration.
        }
    }
}
