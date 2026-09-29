<?php

namespace App\Actions\Product;

use App\Models\SearchLog;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Services\Product\SearchQueryNormalizer;

class IndexProductAction
{
    public function __construct(
        private ProductRepository $productRepo,
        private SearchQueryNormalizer $queryNormalizer,
    ) {}

    public function execute(User $user, array $request)
    {
        $products = $this->productRepo->paginate($user, $request);

        if (isset($request['search']) && trim((string) $request['search']) !== '') {
            $query = mb_substr(trim((string) $request['search']), 0, 500);
            $telemetry = $this->productRepo->lastSearchTelemetry();
            SearchLog::create([
                'query' => $query,
                'normalized_query' => $this->queryNormalizer->normalize($query),
                'result_count' => $products->total(),
                'user_id' => $user->getKey(),
                'search_engine' => $telemetry['engine'] ?? null,
                'duration_ms' => $telemetry['duration_ms'] ?? null,
                'fallback_used' => $telemetry['fallback_used'] ?? false,
            ]);
        }

        return $products;
    }
}
