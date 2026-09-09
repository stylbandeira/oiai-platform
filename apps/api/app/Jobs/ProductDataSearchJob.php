<?php

namespace App\Jobs;

use App\Enums\ProductRefinementStatus;
use App\Helpers\ImageFromUrl;
use App\Models\Product;
use App\Models\ProductDataProviderAttempt;
use App\Repositories\ProductCategoryRepository;
use App\Services\Product\ProductDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProductDataSearchJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(
        ProductDataService $productDataService,
        ProductCategoryRepository $productCategoryRepository,
    ): void
    {
        $productDataService->startRun();
        $maximumProducts = $productDataService->maximumProductsPerRun();

        $products = Product::with('providerAttempts')->whereIn('refined', [
                ProductRefinementStatus::Unrefined->value,
                ProductRefinementStatus::OscbrValidated->value,
                ProductRefinementStatus::CosmosValidated->value,
            ])
            ->whereNotNull('ean')
            ->lazyById()
            ->filter(function (Product $product) use ($productDataService) {
                return $productDataService->hasAvailableProvider(
                    $product->refined,
                    $this->definitivelyUnavailableProviders($product),
                );
            })
            ->take($maximumProducts);

        foreach ($products as $product) {
            $excludedProviders = $this->definitivelyUnavailableProviders($product);
            $isCosmosSupplement = $product->refined === ProductRefinementStatus::CosmosValidated;
            $productData = match ($product->refined) {
                ProductRefinementStatus::CosmosValidated => $productDataService
                    ->getSupplementalProductData(
                    $product->ean,
                    $product->refined,
                    $excludedProviders,
                ),
                ProductRefinementStatus::OscbrValidated => $productDataService
                    ->getUpgradeProductData(
                        $product->ean,
                        $product->refined,
                        $excludedProviders,
                    ),
                default => $productDataService->getProductData(
                    $product->ean,
                    $excludedProviders,
                ),
            };

            $attempts = $productData['_provider_attempts'] ?? [];
            unset($productData['_provider_attempts']);
            $this->recordProviderAttempts($product, $attempts);

            if (! count($productData)) {
                $unavailableProviders = array_values(array_unique(array_merge(
                    $excludedProviders,
                    collect($attempts)
                        ->where('status', 'not_found')
                        ->pluck('provider')
                        ->all(),
                )));

                if (
                    $product->refined === ProductRefinementStatus::Unrefined
                    && ! $productDataService->hasAvailableProvider(
                        $product->refined,
                        $unavailableProviders,
                    )
                ) {
                    $product->refined = ProductRefinementStatus::NotFound;
                    $product->save();
                }

                continue;
            }

            $nextStatus = ProductRefinementStatus::from($productData['refined']);

            if (! $isCosmosSupplement && ! $product->refined->canTransitionTo($nextStatus)) {
                continue;
            }

            $img = '';

            if (
                blank($product->img)
                && isset($productData['image_url'])
                && $productData['image_url'] !== ''
            ) {
                try {
                    $img = app(ImageFromUrl::class)->saveImageFromUrl(
                        $productData['image_url'],
                        $productData['_image_headers'] ?? [],
                    );
                } catch (\Throwable $exception) {
                    Log::warning('Produto será salvo sem imagem após falha no download.', [
                        'product_id' => $product->id,
                        'ean' => $product->ean,
                        'image_url' => $productData['image_url'],
                        'exception' => $exception::class,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $product->img = $img !== '' ? $img : $product->img;

            foreach ([
                'name',
                'raw_name',
                'normalized_name',
                'search_description',
                'ncm',
            ] as $field) {
                if (
                    array_key_exists($field, $productData)
                    && (! $isCosmosSupplement || blank($product->{$field}))
                ) {
                    $product->{$field} = $productData[$field];
                }
            }

            if (
                array_key_exists('normalized_quantity', $productData)
                && (! $isCosmosSupplement || blank($product->normalized_quantity))
            ) {
                foreach ([
                    'normalized_quantity',
                    'quantity_dimension',
                    'quantity_source',
                    'quantity_confidence',
                ] as $field) {
                    if (array_key_exists($field, $productData)) {
                        $product->{$field} = $productData[$field];
                    }
                }
            }

            if (! $isCosmosSupplement) {
                $product->refined = $nextStatus;
            }

            $product->save();

            if (isset($productData['category']) && $productData['category'] !== '') {
                $productCategoryRepository->firstOrNew($productData['category']);
            }
        }
    }

    private function definitivelyUnavailableProviders(Product $product): array
    {
        return $product->providerAttempts
            ->where('status', 'not_found')
            ->pluck('provider')
            ->values()
            ->all();
    }

    private function recordProviderAttempts(Product $product, array $attempts): void
    {
        foreach ($attempts as $attemptData) {
            if (! isset($attemptData['provider'], $attemptData['status'])) {
                continue;
            }

            $attempt = ProductDataProviderAttempt::firstOrNew([
                'product_id' => $product->id,
                'provider' => $attemptData['provider'],
            ]);
            $attempt->status = $attemptData['status'];
            $attempt->http_status = $attemptData['http_status'] ?? null;
            $attempt->message = $attemptData['message'] ?? null;
            $attempt->attempts = ($attempt->exists ? $attempt->attempts : 0) + 1;
            $attempt->last_attempt_at = now();
            $attempt->save();
        }
    }
}
