<?php

namespace App\Services\Product;

use App\Contracts\Product\ProductDataProvider;
use App\Enums\ProductRefinementStatus;
use Illuminate\Support\Facades\Cache;

class ProductDataService
{
    private array $runUsage = [];
    private array $unavailableProviders = [];

    /** @param iterable<ProductDataProvider> $providers */
    public function __construct(private iterable $providers)
    {
    }

    public function startRun(): void
    {
        $this->runUsage = [];
        $this->unavailableProviders = [];
    }

    public function getProductData(string $ean, array $excludedProviders = []): array
    {
        return $this->requestProviders($ean, $this->providers, $excludedProviders);
    }

    public function getSupplementalProductData(
        string $ean,
        ProductRefinementStatus $currentStatus,
        array $excludedProviders = [],
    ): array {
        $currentProviderReached = false;
        $providers = [];

        foreach ($this->providers as $provider) {
            if ($provider->refinementStatus() === $currentStatus) {
                $currentProviderReached = true;
                continue;
            }

            if (! $currentProviderReached) {
                continue;
            }

            $providers[] = $provider;
        }

        return $this->requestProviders($ean, $providers, $excludedProviders);
    }

    public function getUpgradeProductData(
        string $ean,
        ProductRefinementStatus $currentStatus,
        array $excludedProviders = [],
    ): array {
        $providers = [];

        foreach ($this->providers as $provider) {
            if ($provider->refinementStatus() === $currentStatus) {
                break;
            }

            $providers[] = $provider;
        }

        return $this->requestProviders($ean, $providers, $excludedProviders);
    }

    public function hasAvailableProvider(
        ProductRefinementStatus $currentStatus,
        array $excludedProviders = [],
    ): bool {
        foreach ($this->applicableProviders($currentStatus) as $provider) {
            if (! in_array($provider->key(), $excludedProviders, true)) {
                return true;
            }
        }

        return false;
    }

    public function maximumProductsPerRun(): int
    {
        $total = 0;

        foreach ($this->providers as $provider) {
            $total += $provider->batchSize();
        }

        return $total;
    }

    private function requestProvider(ProductDataProvider $provider, string $ean): array
    {
        if (! $this->reserveUsage($provider)) {
            return [];
        }

        return $provider->getProductData($ean);
    }

    private function requestProviders(string $ean, iterable $providers, array $excludedProviders): array
    {
        $attempts = [];

        foreach ($providers as $provider) {
            if (in_array($provider->key(), $excludedProviders, true)) {
                continue;
            }

            $data = $this->requestProvider($provider, $ean);

            if ($data === []) {
                continue;
            }

            $lookup = $data['_lookup'] ?? null;
            unset($data['_lookup']);

            if (is_array($lookup)) {
                $lookup['provider'] = $provider->key();
                $attempts[] = $lookup;
            }

            if (($lookup['status'] ?? null) === 'not_found') {
                continue;
            }

            if ($data !== []) {
                $data['_provider_attempts'] = $attempts;

                return $data;
            }
        }

        return $attempts === [] ? [] : ['_provider_attempts' => $attempts];
    }

    /** @return array<ProductDataProvider> */
    private function applicableProviders(ProductRefinementStatus $currentStatus): array
    {
        $providers = is_array($this->providers)
            ? $this->providers
            : iterator_to_array($this->providers);

        if ($currentStatus === ProductRefinementStatus::Unrefined) {
            return $providers;
        }

        $currentIndex = null;

        foreach ($providers as $index => $provider) {
            if ($provider->refinementStatus() === $currentStatus) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null) {
            return [];
        }

        if ($currentStatus === ProductRefinementStatus::CosmosValidated) {
            return array_slice($providers, $currentIndex + 1);
        }

        return array_slice($providers, 0, $currentIndex);
    }

    private function reserveUsage(ProductDataProvider $provider): bool
    {
        $providerKey = $provider->key();

        if (
            isset($this->unavailableProviders[$providerKey])
            || ($this->runUsage[$providerKey] ?? 0) >= $provider->batchSize()
        ) {
            return false;
        }

        $date = now()->format('Y-m-d');
        $dailyKey = "product-data:{$providerKey}:daily:{$date}";
        $lastRunKey = "product-data:{$providerKey}:last-run";
        $lock = Cache::lock("product-data:{$providerKey}:usage-lock", 10);

        $reserved = $lock->block(2, function () use ($provider, $dailyKey, $lastRunKey) {
            $dailyUsage = (int) Cache::get($dailyKey, 0);

            if ($dailyUsage >= $provider->dailyLimit()) {
                return false;
            }

            if (($this->runUsage[$provider->key()] ?? 0) === 0) {
                $lastRun = Cache::get($lastRunKey);

                if (
                    $lastRun !== null
                    && \Carbon\Carbon::createFromTimestamp((int) $lastRun)
                        ->diffInMinutes(now(), true) < $provider->recurrenceMinutes()
                ) {
                    return false;
                }

                Cache::put($lastRunKey, now()->timestamp, now()->endOfDay());
            }

            Cache::put($dailyKey, $dailyUsage + 1, now()->endOfDay());

            return true;
        });

        if (! $reserved) {
            $this->unavailableProviders[$providerKey] = true;

            return false;
        }

        $this->runUsage[$providerKey] = ($this->runUsage[$providerKey] ?? 0) + 1;

        return true;
    }
}
