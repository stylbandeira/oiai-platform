<?php

namespace App\Jobs;

use App\Events\ProductEnriched;
use App\Jobs\IndexProductJob;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class NormalizeProductJob implements ShouldQueue
{
    use Queueable;

    public const VERSION = 2;

    public function __construct(public readonly int $productId)
    {
    }

    public function handle(): void
    {
        $product = Product::find($this->productId);
        if (! $product || $product->normalization_version >= self::VERSION) {
            return;
        }

        $name = trim((string) ($product->raw_name ?: $product->name));
        $normalizedName = preg_replace('/\s+/', ' ', $name) ?: $name;
        $quantity = $product->normalized_quantity;
        if (! $quantity && preg_match('/(\d+(?:[,.]\d+)?)\s*(kg|g|mg|l|ml|un|und)/iu', $name, $match)) {
            $quantity = str_replace(',', '.', $match[1]).' '.strtolower($match[2]);
        }

        $product->forceFill([
            'normalized_name' => $normalizedName,
            'normalized_quantity' => $quantity,
            'search_description' => trim((string) ($product->search_description ?: $product->description)),
            'normalization_version' => self::VERSION,
            'normalized_at' => now(),
        ])->saveQuietly();

        ProductEnriched::dispatch($this->productId);
        IndexProductJob::dispatch($this->productId);
    }
}
