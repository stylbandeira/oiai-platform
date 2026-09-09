<?php

namespace App\Services\Product\Providers;

use App\Contracts\Product\ProductDataProvider;
use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
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
        $token = $this->authenticate();

        if ($token === null) {
            return [];
        }

        $url = rtrim((string) config('services.oscbr.product_url'), '/')
            .'/'.rawurlencode($ean);
        Log::info('Consultando dados do produto na OSCBR.', ['ean' => $ean, 'url' => $url]);

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->get($url);
        } catch (\Throwable $exception) {
            Log::warning('Falha de conexão com a OSCBR.', [
                'ean' => $ean,
                'url' => $url,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
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
            Log::warning('Resposta da OSCBR não contém os campos mínimos esperados.', [
                'ean' => $ean,
                'url' => $url,
                'status' => $response->status(),
                'response_keys' => is_array($payload) ? array_keys($payload) : [],
            ]);

            return [];
        }

        if (! blank($data['image_url'] ?? null)) {
            $data['_image_headers'] = ['Authorization' => 'Bearer '.$token];
        }

        return $data + $this->lookupResult('found', $response->status());
    }

    private function authenticate(): ?string
    {
        $login = (string) config('services.oscbr.login');
        $password = (string) config('services.oscbr.password');

        if ($login === '' || $password === '') {
            Log::warning('Credenciais da OSCBR não configuradas.');

            return null;
        }

        $url = (string) config('services.oscbr.auth_url');

        try {
            $response = Http::withBasicAuth($login, $password)
                ->acceptJson()
                ->timeout(20)
                ->post($url);
        } catch (\Throwable $exception) {
            Log::warning('Falha ao autenticar na OSCBR.', [
                'url' => $url,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning(
                'Autenticação recusada pela OSCBR; consulta ao produto não foi realizada.',
                $this->responseContext('authentication', $url, $response),
            );

            return null;
        }

        $token = $response->json('token') ?? $response->json('access_token');

        if (! is_string($token) || $token === '') {
            Log::warning('A OSCBR não retornou um token válido.');

            return null;
        }

        return $token;
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
