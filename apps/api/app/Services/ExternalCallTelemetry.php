<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        } catch (\Throwable) {
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
        } catch (\Throwable) {
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
}
