<?php

namespace App\Services\Product\Providers;

use App\Contracts\Product\ProductDataProvider;
use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use App\Services\ExternalCallTelemetry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OscbrProductDataProvider extends AbstractProductDataProvider implements ProductDataProvider
{
    public function key(): string
    {
        return 'oscbr';
    }

    public function refinementStatus(): ProductRefinementStatus
    {
        return ProductRefinementStatus::OscbrValidated;
    }

    public function batchSize(): int
    {
        return max(0, (int) config('services.oscbr.batch_size', 5));
    }

    public function recurrenceMinutes(): int
    {
        return max(1, (int) config('services.oscbr.recurrence_minutes', 5));
    }

    public function dailyLimit(): int
    {
        return max(0, (int) config('services.oscbr.daily_limit', 50));
    }

    public function getProductData(string $ean): array
    {
        $correlationId = app(ExternalCallTelemetry::class)->correlationId();
        $token = $this->authenticate($correlationId);

        if ($token === null) {
            return [];
        }

        $url = rtrim((string) config('services.oscbr.product_url'), '/')
            .'/'.rawurlencode($ean);
        Log::info('Consultando dados do produto na OSCBR.', ['ean' => $ean, 'url' => $url]);

        $startedAt = microtime(true);
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->get($url);
        } catch (\Throwable $exception) {
            $this->recordCall(
                'product.lookup',
                $this->exceptionResult($exception),
                null,
                $startedAt,
                $correlationId,
                $exception,
            );
            Log::warning('Falha de conexão com a OSCBR.', [
                'ean' => $ean,
                'url' => $url,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        if ($response->successful() === false) {
            $this->recordCall('product.lookup', $response->status() === 404 ? 'not_found' : 'http_error', $response->status(), $startedAt, $correlationId);
            $context = $this->responseContext($ean, $url, $response);

            if ($response->status() === 404) {
                Log::info('Produto não encontrado na OSCBR; consulta marcada como definitiva.', $context);

                return $this->lookupResult('not_found', 404, $context['response_message']);
            }

            Log::warning('Falha temporária ao consultar produto na OSCBR; nova tentativa será permitida.', $context);

            return [];
        }

        $payload = $response->json();

        $data = is_array($payload) ? $this->normalize($payload) : [];

        if ($data === []) {
            $this->recordCall('product.lookup', 'invalid_payload', $response->status(), $startedAt, $correlationId);
            Log::warning('Resposta da OSCBR não contém os campos mínimos esperados.', [
                'ean' => $ean,
                'url' => $url,
                'status' => $response->status(),
                'response_keys' => is_array($payload) ? array_keys($payload) : [],
            ]);

            return [];
        }

        $this->recordCall('product.lookup', 'success', $response->status(), $startedAt, $correlationId);

        if (blank($data['image_url'] ?? null) === false) {
            $data['_image_headers'] = ['Authorization' => 'Bearer '.$token];
        }

        return $data + $this->lookupResult('found', $response->status());
    }

    private function authenticate(string $correlationId): ?string
    {
        $login = (string) config('services.oscbr.login');
        $password = (string) config('services.oscbr.password');

        if ($login === '' || $password === '') {
            Log::warning('Credenciais da OSCBR não configuradas.');

            return null;
        }

        $url = (string) config('services.oscbr.auth_url');

        $startedAt = microtime(true);
        try {
            $response = Http::withBasicAuth($login, $password)
                ->acceptJson()
                ->timeout(20)
                ->post($url);
        } catch (\Throwable $exception) {
            $this->recordCall(
                'auth.token',
                $this->exceptionResult($exception),
                null,
                $startedAt,
                $correlationId,
                $exception,
            );
            Log::warning('Falha ao autenticar na OSCBR.', [
                'url' => $url,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($response->successful() === false) {
            $this->recordCall('auth.token', 'http_error', $response->status(), $startedAt, $correlationId);
            Log::warning(
                'Autenticação recusada pela OSCBR; consulta ao produto não foi realizada.',
                $this->responseContext('authentication', $url, $response),
            );

            return null;
        }

        $token = $response->json('token') ?? $response->json('access_token');

        if (is_string($token) === false || $token === '') {
            $this->recordCall('auth.token', 'invalid_payload', $response->status(), $startedAt, $correlationId);
            Log::warning('A OSCBR não retornou um token válido.');

            return null;
        }

        $this->recordCall('auth.token', 'success', $response->status(), $startedAt, $correlationId);
        return $token;
    }

    private function recordCall(
        string $operation,
        string $result,
        ?int $status,
        float $startedAt,
        string $correlationId,
        ?\Throwable $exception = null,
    ): void {
        app(ExternalCallTelemetry::class)->record([
            'provider' => 'oscbr',
            'operation' => $operation,
            'result' => $result,
            'http_status' => $status,
            'duration_ms' => (microtime(true) - $startedAt) * 1000,
            'failure_type' => $exception
                ? ($result === 'timeout' ? 'timeout' : 'connection')
                : ($result === 'http_error' ? 'http' : ($result === 'invalid_payload' ? 'invalid_payload' : null)),
            'correlation_id' => $correlationId,
            'error_class' => $exception ? $exception::class : null,
        ]);
    }

    private function exceptionResult(\Throwable $exception): string
    {
        return str_contains(strtolower($exception->getMessage()), 'timed out')
            || str_contains(strtolower($exception->getMessage()), 'timeout')
            ? 'timeout'
            : 'connection_error';
    }

    public function normalize(array $payload): array
    {
        $rawName = trim((string) ($payload['nome'] ?? $payload['name'] ?? ''));

        if ($rawName === '') {
            return [];
        }

        $source = ProductQuantitySource::DefaultExtraction;
        $normalizedName = $this->normalizeText($rawName);
        $category = trim((string) ($payload['categoria'] ?? $payload['category'] ?? ''));
        $brand = trim((string) ($payload['marca'] ?? $payload['brand'] ?? ''));
        $ncm = trim((string) ($payload['ncm'] ?? ''));
        $quantity = $this->extractQuantityFromName($rawName);

        $data = [
            'name' => $rawName,
            'raw_name' => $rawName,
            'normalized_name' => $normalizedName,
            'search_description' => trim(implode(' ', array_filter([
                $normalizedName,
                $this->normalizeText($brand),
                $this->normalizeText($category),
            ]))),
            'ncm' => $this->normalizeNcm($ncm),
            'image_url' => $payload['link_foto']
                ?? $payload['image_url']
                ?? $payload['imagem']
                ?? '',
            'category' => $category,
            'refined' => $this->refinementStatus()->value,
        ];

        if ($quantity !== null) {
            $data['normalized_quantity'] = $quantity;
            $data['quantity_dimension'] = str_ends_with($quantity, ' ml') ? 'volume' : 'mass';
            $data['quantity_source'] = $source->value;
            $data['quantity_confidence'] = $source->confidence();
        }

        return $data;
    }
}
