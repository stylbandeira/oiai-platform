<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Product\ProductOperationMonitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class IndexProductJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $productId,
        public readonly int $documentVersion = Product::SEARCH_DOCUMENT_VERSION,
    ) {}

    public function handle(ProductOperationMonitor $monitor): void
    {
        $product = Product::find($this->productId);
        if (! $product) {
            return;
        }

        $monitor->measure('indexing', $product, function () use ($product): void {
            // This job already runs outside the request; indexing synchronously here
            // avoids creating an untracked second Scout queue job.
            $product->searchableSync();

            DB::table('products')->where('id', $product->getKey())->update([
                'search_document_version' => $this->documentVersion,
                'search_indexed_at' => now(),
            ]);
        });
    }
}
