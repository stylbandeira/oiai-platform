<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Product\ProductOperationMonitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class IndexProductsBatchJob implements ShouldQueue
{
    use Queueable;

    /** @param list<int> $productIds */
    public function __construct(
        public readonly array $productIds,
        public readonly int $documentVersion = Product::SEARCH_DOCUMENT_VERSION,
    ) {}

    public function handle(ProductOperationMonitor $monitor): void
    {
        $products = Product::query()->whereKey($this->productIds)->get();
        if ($products->isEmpty()) {
            return;
        }

        $monitor->measure('indexing', null, function () use ($products): void {
            $products->searchableSync();

            DB::table('products')->whereIn('id', $products->modelKeys())->update([
                'search_document_version' => $this->documentVersion,
                'search_indexed_at' => now(),
            ]);
        });
    }
}
