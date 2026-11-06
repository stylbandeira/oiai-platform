<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AdminBurasDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'environment' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_.:-]{1,80}$/'],
            'provider' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_.:-]{1,80}$/'],
            'period' => ['nullable', 'in:1h,6h,24h,72h,168h,720h'],
        ]);

        $baseUrl = rtrim((string) config('services.buras.url'), '/');
        $token = (string) config('services.buras.admin_token');

        if ($baseUrl === '' || $token === '') {
            return response()->json([
                'message' => 'O painel do Buras não está configurado neste ambiente.',
            ], 503);
        }

        $environment = $filters['environment'] ?? (string) config('app.env');
        $provider = $filters['provider'] ?? '';
        $period = $filters['period'] ?? '24h';

        try {
            $responses = Http::pool(function (Pool $pool) use ($baseUrl, $token, $environment, $provider, $period): void {
                $query = ['environment' => $environment, 'provider' => $provider];

                $pool->as('summary')
                    ->withToken($token)
                    ->acceptJson()
                    ->connectTimeout(1)
                    ->timeout(4)
                    ->get($baseUrl.'/v1/summary', [...$query, 'period' => $period]);

                $pool->as('events')
                    ->withToken($token)
                    ->acceptJson()
                    ->connectTimeout(1)
                    ->timeout(4)
                    ->get($baseUrl.'/v1/events', $query);

                $pool->as('checks')
                    ->withToken($token)
                    ->acceptJson()
                    ->connectTimeout(1)
                    ->timeout(4)
                    ->get($baseUrl.'/v1/checks', $query);

                $pool->as('alerts')
                    ->withToken($token)
                    ->acceptJson()
                    ->connectTimeout(1)
                    ->timeout(4)
                    ->get($baseUrl.'/v1/alerts', $query);
            });

            foreach (['summary', 'events', 'checks', 'alerts'] as $key) {
                if (! $responses[$key]->successful()) {
                    return response()->json([
                        'message' => 'O Buras está indisponível ou recusou a consulta.',
                    ], 503);
                }
            }

            return response()->json([
                'summary' => $this->rows($responses['summary']),
                'events' => $this->rows($responses['events']),
                'checks' => $this->rows($responses['checks']),
                'alerts' => $this->rows($responses['alerts']),
                'environment' => $environment,
                'provider' => $provider,
                'period' => $period,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Buras dashboard request failed.', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'Não foi possível consultar o Buras agora.',
            ], 503);
        }
    }

    /** @return array<int, mixed> */
    private function rows(Response $response): array
    {
        $data = $response->json();

        return is_array($data) ? array_values($data) : [];
    }
}
