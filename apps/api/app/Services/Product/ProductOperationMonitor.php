<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductPipelineRun;
use Closure;
use Throwable;

final class ProductOperationMonitor
{
    /**
     * @template T
     *
     * @param  Closure(): T  $operation
     * @return T
     */
    public function measure(string $operationName, ?Product $product, Closure $operation): mixed
    {
        $startedAt = now();
        $started = hrtime(true);
        $run = ProductPipelineRun::create([
            'product_id' => $product?->getKey(),
            'operation' => $operationName,
            'status' => 'running',
            'started_at' => $startedAt,
        ]);

        try {
            $result = $operation();

            $run->update([
                'status' => 'completed',
                'duration_ms' => $this->elapsedMilliseconds($started),
                'finished_at' => now(),
            ]);

            return $result;
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'duration_ms' => $this->elapsedMilliseconds($started),
                'error_message' => mb_substr($exception->getMessage(), 0, 65535),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }

    private function elapsedMilliseconds(int $started): int
    {
        return max(1, (int) round((hrtime(true) - $started) / 1_000_000));
    }
}
