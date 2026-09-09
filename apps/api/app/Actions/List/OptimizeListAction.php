<?php

namespace App\Actions\List;

use App\Http\Resources\ClientProductResource;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Repositories\CompanyProductsRepository;
use App\Repositories\ListProductsRepository;
use App\Repositories\ListRepository;
use App\Services\Geolocation\GeolocationService;
use Illuminate\Support\Collection;

class OptimizeListAction
{
    public function __construct(
        private CompanyProductsRepository $companyProductsRepository,
        private ListProductsRepository $listProductsRepository,
        private ListRepository $listRepository,
        private GeolocationService $geolocationService,
    ) {}
    public function execute(string $list_id)
    {
        $list = ItensList::with('products')->find($list_id);

        $optimizedList = [];

        $companyProducts = $this->companyProductsRepository->getByProductIdsWithPivots($list->products->pluck('id')->toArray());

        $cheapest = $companyProducts->groupBy('product_id')
            ->map(fn(Collection $items) => $this->selectBestOffer($items, $list))
            ->filter();

        foreach ($cheapest as $cheap) {
            $product = (new ClientProductResource($cheap->product))->resolve();
            $product['average_price'] = (float) $cheap->average_price;

            $optimizedList[$cheap->company->name][] = $product;

            $this->listProductsRepository->updateProductsOnList([$cheap->product_id], $list->id, [
                'company_product_id' => $cheap->id,
            ]);
        }

        $this->listRepository->update($list->id, ['optimized' => true]);

        return $optimizedList;
    }

    private function selectBestOffer(Collection $offers, ItensList $list): ?CompanyProducts
    {
        $validOffers = $offers
            ->filter(
                fn(CompanyProducts $offer) => $offer->average_price !== null
                    && (float) $offer->average_price > 0
            );

        if ($validOffers->isEmpty()) {
            return null;
        }

        if (
            $list->distance !== null
            && $list->latitude !== null
            && $list->longitude !== null
        ) {
            $offersWithinDistance = $validOffers->filter(
                fn(CompanyProducts $offer) => $this->isWithinDistance($offer, $list)
            );

            if ($offersWithinDistance->isNotEmpty()) {
                $validOffers = $offersWithinDistance;
            }
        }

        return $validOffers
            ->sortBy(fn(CompanyProducts $offer) => (float) $offer->average_price)
            ->first();
    }

    private function isWithinDistance(CompanyProducts $offer, ItensList $list): bool
    {
        $distance = $this->geolocationService->between([
            'latitude' => $offer->company?->address?->latitude,
            'longitude' => $offer->company?->address?->longitude,
        ], [
            'latitude' => $list->latitude,
            'longitude' => $list->longitude,
        ]);

        return $distance !== null && $distance <= (float) $list->distance;
    }
}
