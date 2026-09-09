<?php

namespace App\Services;

use App\Contracts\NFCe\StateNFCeProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NFCeScraperService
{
    /** @param array<StateNFCeProvider> $stateProviders */
    public function __construct(private array $stateProviders) {}

    public function scrapeFromQRCode(string $qrData): array
    {
        // A camera can emit the same code several times before the UI stops it.
        // Normalize it and reuse a recent successful lookup instead of hitting
        // the SEFAZ portal once per frame/request.
        $qrData = trim(preg_replace('/\s+/', '', $qrData) ?? $qrData);
        $cacheKey = 'nfce:qr:' . hash('sha256', $qrData);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        foreach ($this->stateProviders as $provider) {
            if (! $provider->supports($qrData)) {
                continue;
            }

            try {
                $result = $provider->scrapeFromQRCode($qrData);

                if (($result['status'] ?? null) === 'success') {
                    Cache::put($cacheKey, $result, now()->addMinutes(10));
                }

                return $result;
            } catch (\Throwable $exception) {
                Log::error('Erro ao processar NFCe no provider estadual.', [
                    'provider' => $provider::class,
                    'qr_data' => $qrData,
                    'error' => $exception->getMessage(),
                ]);

                return [
                    'status' => 'error',
                    'error' => $exception->getMessage(),
                    'qr_data' => $qrData,
                ];
            }
        }

        return [
            'status' => 'error',
            'error' => 'Não existe provider cadastrado para a UF informada.',
            'qr_data' => $qrData,
        ];
    }
}
