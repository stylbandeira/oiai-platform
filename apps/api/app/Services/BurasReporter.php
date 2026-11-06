<?php

namespace App\Services;

use App\Jobs\Telemetry\SendBurasEvent;
use Illuminate\Support\Str;
use Throwable;

final class BurasReporter
{
    /** @param array<string, mixed> $fields */
    public function record(array $fields): void
    {
        if (blank(config('services.buras.url')) || blank(config('services.buras.token'))) {
            return;
        }

        try {
            $event = [
                'event_id' => (string) Str::ulid(),
                'event' => ($fields['event'] ?? null) === 'exception' ? 'exception' : 'external_call',
                'occurred_at' => now()->utc()->format('Y-m-d\TH:i:s.u\Z'),
                'environment' => $this->safeName((string) ($fields['environment'] ?? config('app.env'))),
                'provider' => $this->safeName((string) ($fields['provider'] ?? 'unknown')),
                'operation' => $this->safeName((string) ($fields['operation'] ?? 'unknown')),
                'result' => $this->safeResult((string) ($fields['result'] ?? 'failure')),
                'http_status' => $this->safeStatus($fields['http_status'] ?? null),
                'duration_ms' => $this->safeDuration($fields['duration_ms'] ?? null),
                'failure_type' => isset($fields['failure_type']) ? $this->safeName((string) $fields['failure_type']) : null,
                'correlation_id' => $this->safeCorrelation((string) ($fields['correlation_id'] ?? '')),
                'error_class' => isset($fields['error_class']) ? $this->safeClass((string) $fields['error_class']) : null,
            ];

            SendBurasEvent::dispatch($event);
        } catch (Throwable) {
            // Observability must never interrupt a business request or job.
        }
    }

    public function reportException(Throwable $exception, string $correlationId): void
    {
        $this->record([
            'event' => 'exception',
            'provider' => 'oiai-api',
            'operation' => 'exception.unhandled',
            'result' => 'failure',
            'failure_type' => 'exception',
            'error_class' => $exception::class,
            'correlation_id' => $correlationId,
        ]);
    }

    private function safeName(string $value): string
    {
        return preg_match('/^[A-Za-z0-9_.:-]{1,80}$/D', $value) === 1 ? $value : 'unknown';
    }

    private function safeCorrelation(string $value): string
    {
        if (preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/D', $value) === 1
            || preg_match('/^[0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}$/D', $value) === 1) {
            return $value;
        }

        return (string) Str::ulid();
    }

    private function safeClass(string $value): ?string
    {
        return preg_match('/^[A-Za-z0-9_\\\\]{1,160}$/D', $value) === 1 ? $value : null;
    }

    private function safeResult(string $value): string
    {
        return in_array($value, ['success', 'not_found', 'skipped', 'failure', 'timeout', 'connection_error', 'http_error', 'invalid_payload', 'rate_limited', 'unauthorized'], true)
            ? $value
            : 'failure';
    }

    private function safeStatus(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $status = (int) $value;

        return $status >= 100 && $status <= 599 ? $status : null;
    }

    private function safeDuration(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $duration = (float) $value;

        return is_finite($duration) && $duration >= 0 && $duration <= 3600000 ? round($duration, 2) : null;
    }
}
