<?php

namespace App\Services\Lists;

use App\Contracts\ListDataAssembler;
use App\Http\Resources\ClientProductResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ListProductResource;
use App\Models\ItensList;
use App\Repositories\ListRepository;
use App\Services\Geolocation\GeolocationService;

class EloquentListDataAssembler implements ListDataAssembler
{
    public function __construct(
        private ListRepository $listRepository,
        private GeolocationService $geolocation_service
    ) {}

    public function assemble(ItensList $list): array
    {
        $this->listRepository->loadDefaultRelations($list);

        $data = [
            'id' => $list->id,
            'optimized' => (bool) $list->optimized,
            'user_id' => $list->user_id,
            'name' => $list->name,
            'favorite' => (bool) $list->favorite,
            'status' => $list->status,
            'total' => $this->calculateTotal($list),
            'created_at' => $list->created_at,
            'companies' => [],
            'products' => ListProductResource::collection($list->listProducts)->resolve(),
            'productsQuantity' => $list->listProducts->count(),
        ];

        if (! $list->optimized) {
            return $data;
        }

        foreach ($list->listProducts as $listProduct) {
            $companyProduct = $listProduct->companyProduct;

            if (! $companyProduct?->company) {
                continue;
            }

            $company = $companyProduct->company;

            $distance = $this->geolocation_service->between([
                'latitude' => $company->address?->latitude ?? null,
                'longitude' => $company->address?->longitude ?? null
            ], [
                'latitude' => $list->latitude,
                'longitude' => $list->longitude
            ]);

            $isTooFar = $list->distance !== null
                && ($distance === null || $distance > (float) $list->distance);

            $data['companies'][$company->id] ??= [
                'company' => (new CompanyResource($company))->resolve(),
                'distance' => $distance,
                'isTooFar' => $isTooFar,
                'products' => [],
            ];

            $data['companies'][$company->id]['products'][] = [
                'product' => (new ClientProductResource($listProduct->product))->resolve(),
                'average_price' => (float) $companyProduct->average_price,
            ];
        }

        uasort(
            $data['companies'],
            function (array $a, array $b): int {
                if ($a['distance'] === null) {
                    return $b['distance'] === null ? 0 : 1;
                }

                if ($b['distance'] === null) {
                    return -1;
                }

                return $a['distance'] <=> $b['distance'];
            }
        );

        return $data;
    }

    private function calculateTotal(ItensList $list): float
    {
        return (float) $list->listProducts->sum(function ($listProduct) use ($list) {
            $unitPrice = $list->optimized
                ? $listProduct->companyProduct?->average_price
                : $listProduct->product?->average_price;

            return (float) $unitPrice * (float) $listProduct->quantity;
        });
    }
}
