<?php

namespace App\Services\Product\Providers;

use App\Contracts\Product\ProductDataProvider;
use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CosmosProductDataProvider extends AbstractProductDataProvider implements ProductDataProvider
{
    public function key(): string
    {
        return 'cosmos';
    }

    public function refinementStatus(): ProductRefinementStatus
    {
        return ProductRefinementStatus::CosmosValidated;
    }

    public function batchSize(): int
    {
        return max(0, (int) config('services.cosmos.batch_size', 7));
    }

    public function recurrenceMinutes(): int
    {
        return max(1, (int) config('services.cosmos.recurrence_minutes', 180));
    }

    public function dailyLimit(): int
    {
        return max(0, (int) config('services.cosmos.daily_limit', 45));
    }

    public function getProductData(string $ean): array
    {
        $token = (string) config('services.cosmos.token');

        if ($token === '') {
            Log::warning('Consulta à Cosmos ignorada: token não configurado.', ['ean' => $ean]);

            return [];
        }

        $url = rtrim((string) config('services.cosmos.url'), '/')
            .'/gtins/'.rawurlencode($ean).'.json';
        Log::info('Consultando dados do produto na Cosmos.', ['ean' => $ean, 'url' => $url]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-Cosmos-Token' => $token,
            ])
                ->withUserAgent((string) config('services.cosmos.user_agent'))
                ->timeout(20)
                ->get($url);
        } catch (\Throwable $exception) {
            Log::warning('Falha de conexão com a Cosmos.', [
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
                Log::info('Produto não encontrado na Cosmos; consulta marcada como definitiva.', $context);

                return $this->lookupResult('not_found', 404, $context['response_message']);
            }

            Log::warning('Falha temporária ao consultar produto na Cosmos; nova tentativa será permitida.', $context);

            return [];
        }

        $payload = $response->json();

        $data = is_array($payload) ? $this->normalize($payload) : [];

        if ($data === []) {
            Log::warning('Resposta da Cosmos não contém os campos mínimos esperados.', [
                'ean' => $ean,
                'url' => $url,
                'status' => $response->status(),
                'response_keys' => is_array($payload) ? array_keys($payload) : [],
            ]);

            return [];
        }

        return $data + $this->lookupResult('found', $response->status());
    }

    public function normalize(array $payload): array
    {
        $rawName = trim((string) ($payload['description'] ?? ''));

        if ($rawName === '') {
            return [];
        }

        $source = ProductQuantitySource::CosmosExtraction;
        $normalizedName = $this->normalizeText($rawName);
        $category = data_get($payload, 'category.description')
            ?? data_get($payload, 'gpc.description', '');
        $ncm = data_get($payload, 'ncm.full_description')
            ?? data_get($payload, 'ncm.description', '');
        $weight = $this->positiveNumber($payload['net_weight'] ?? null)
            ?? $this->positiveNumber($payload['gross_weight'] ?? null);
        $quantity = $weight !== null
            ? $this->formatNumber($weight) . ' g'
            : $this->extractQuantityFromName($rawName);

        $data = [
            'name' => $rawName,
            'raw_name' => $rawName,
            'normalized_name' => $normalizedName,
            'search_description' => trim(implode(' ', array_filter([
                $normalizedName,
                $this->normalizeText((string) data_get($payload, 'brand.name', '')),
                $this->normalizeText((string) $category),
                $this->normalizeText((string) $ncm),
            ]))),
            'ncm' => $this->normalizeNcm(data_get($payload, 'ncm.code')),
            'image_url' => data_get($payload, 'gtins.barcode_image')
                ?? ($payload['thumbnail'] ?? ''),
            'category' => (string) $category,
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
