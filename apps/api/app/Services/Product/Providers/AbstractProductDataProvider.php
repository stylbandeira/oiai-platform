<?php

namespace App\Services\Product\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

abstract class AbstractProductDataProvider
{
    protected function lookupResult(
        string $status,
        ?int $httpStatus = null,
        ?string $message = null,
    ): array {
        return [
            '_lookup' => [
                'status' => $status,
                'http_status' => $httpStatus,
                'message' => $message,
            ],
        ];
    }

    protected function responseContext(string $ean, string $url, Response $response): array
    {
        return [
            'ean' => $ean,
            'url' => $url,
            'status' => $response->status(),
            'content_type' => $response->header('Content-Type'),
            'request_id' => $response->header('X-Request-Id')
                ?? $response->header('X-Correlation-Id')
                ?? $response->header('Cf-Ray'),
            'response_message' => $this->responseMessage($response),
        ];
    }

    protected function responseMessage(Response $response): ?string
    {
        $payload = $response->json();
        $message = is_array($payload)
            ? data_get($payload, 'message')
                ?? data_get($payload, 'mensagem')
                ?? data_get($payload, 'error_description')
                ?? data_get($payload, 'error')
                ?? data_get($payload, 'erro')
            : null;
        $message = is_scalar($message) ? (string) $message : $response->body();
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? '');

        return $message === '' ? null : Str::limit($message, 500);
    }

    protected function extractQuantityFromName(string $name): ?string
    {
        if (! preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|g|ml|l)\b/iu', $name, $matches)) {
            return null;
        }

        $value = (float) str_replace(',', '.', $matches[1]);
        $unit = strtolower($matches[2]);

        if ($unit === 'kg' || $unit === 'l') {
            $value *= 1000;
            $unit = $unit === 'kg' ? 'g' : 'ml';
        }

        return $this->formatNumber($value).' '.$unit;
    }

    protected function normalizeText(string $value): string
    {
        return trim(preg_replace(
            '/\s+/',
            ' ',
            preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($value))) ?? '',
        ) ?? '');
    }

    protected function positiveNumber(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value > 0 ? (float) $value : null;
    }

    protected function normalizeNcm(mixed $ncm): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $ncm) ?? '';

        return $digits !== '' ? str_pad($digits, 8, '0', STR_PAD_LEFT) : null;
    }

    protected function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
