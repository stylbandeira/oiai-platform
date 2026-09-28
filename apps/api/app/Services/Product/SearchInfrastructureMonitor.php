<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductPipelineRun;
use App\Models\SearchLog;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Meilisearch\Client;
use Meilisearch\Contracts\TasksQuery;
use Throwable;

final class SearchInfrastructureMonitor
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        return [
            'queue' => $this->queueMetrics(),
            'pipeline' => $this->pipelineMetrics(),
            'products' => $this->productMetrics(),
            'meilisearch' => $this->meilisearchMetrics(),
        ];
    }

    /** @return array<string, int|null> */
    private function queueMetrics(): array
    {
        $oldestCreatedAt = DB::table('jobs')->whereNull('reserved_at')->min('created_at');

        return [
            'pending' => DB::table('jobs')->whereNull('reserved_at')->count(),
            'reserved' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'oldest_pending_seconds' => is_numeric($oldestCreatedAt)
                ? max(0, now()->timestamp - (int) $oldestCreatedAt)
                : null,
        ];
    }

    /** @return array<string, int|float|null> */
    private function pipelineMetrics(): array
    {
        $since = now()->subDay();

        return [
            'normalization_average_ms' => $this->averageDuration('normalization', $since),
            'indexing_average_ms' => $this->averageDuration('indexing', $since),
            'normalization_failures' => ProductPipelineRun::query()
                ->where('operation', 'normalization')->where('status', 'failed')->where('created_at', '>=', $since)->count(),
            'indexing_failures' => ProductPipelineRun::query()
                ->where('operation', 'indexing')->where('status', 'failed')->where('created_at', '>=', $since)->count(),
        ];
    }

    /** @return array<string, int> */
    private function productMetrics(): array
    {
        return [
            'total' => Product::query()->count(),
            'stale' => $this->staleProductCount(),
            'document_version' => Product::SEARCH_DOCUMENT_VERSION,
        ];
    }

    public function staleProductCount(): int
    {
        return Product::query()
            ->where(function ($query): void {
                $query->where('search_document_version', '<', Product::SEARCH_DOCUMENT_VERSION)
                    ->orWhereNull('search_indexed_at')
                    ->orWhereColumn('updated_at', '>', 'search_indexed_at');
            })
            ->count();
    }

    /** @return array<string, mixed> */
    private function meilisearchMetrics(): array
    {
        $host = rtrim((string) config('scout.meilisearch.host'), '/');
        $key = config('scout.meilisearch.key');
        $apiKey = is_string($key) ? $key : null;
        $client = new Client($host, $apiKey);
        $indexName = (new Product)->searchableAs();

        try {
            $globalStats = $client->stats();
            $index = $client->index($indexName);
            $indexStats = $index->stats();
            $pendingTasks = (new TasksQuery)->setStatuses(['enqueued', 'processing'])->setLimit(1);
            $failedTasks = (new TasksQuery)->setStatuses(['failed'])->setLimit(5)->setReverse(true);
            $failedResults = $index->getTasks($failedTasks);

            return [
                'available' => true,
                'healthy' => $client->isHealthy(),
                'documents' => (int) ($indexStats['numberOfDocuments'] ?? 0),
                'is_indexing' => (bool) ($indexStats['isIndexing'] ?? false),
                'pending_tasks' => $index->getTasks($pendingTasks)->getTotal(),
                'failed_tasks' => $failedResults->getTotal(),
                'recent_failed_tasks' => $failedResults->getResults(),
                'database_size_bytes' => (int) ($globalStats['databaseSize'] ?? 0),
                'used_database_size_bytes' => (int) ($globalStats['usedDatabaseSize'] ?? 0),
                'memory_bytes' => $this->meilisearchMemory($host, $apiKey),
                'average_search_ms' => $this->averageMeilisearchSearchDuration(),
            ];
        } catch (Throwable $exception) {
            return [
                'available' => false,
                'healthy' => false,
                'error' => $exception->getMessage(),
                'average_search_ms' => $this->averageMeilisearchSearchDuration(),
            ];
        }
    }

    private function averageDuration(string $operation, DateTimeInterface $since): ?float
    {
        $average = ProductPipelineRun::query()
            ->where('operation', $operation)
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->avg('duration_ms');

        return $average === null ? null : round((float) $average, 2);
    }

    private function averageMeilisearchSearchDuration(): ?float
    {
        $average = SearchLog::query()
            ->where('search_engine', 'meilisearch')
            ->where('created_at', '>=', now()->subDay())
            ->avg('duration_ms');

        return $average === null ? null : round((float) $average, 2);
    }

    private function meilisearchMemory(string $host, ?string $key): ?int
    {
        try {
            $request = Http::timeout(3)->accept('text/plain');
            if ($key !== null && $key !== '') {
                $request = $request->withToken($key);
            }

            $body = $request->get($host.'/metrics')->throw()->body();
            if (preg_match('/^(?:meilisearch_)?process_resident_memory_bytes(?:\{[^}]*\})?\s+([0-9.eE+\-]+)/m', $body, $match) !== 1) {
                return null;
            }

            return (int) round((float) $match[1]);
        } catch (Throwable) {
            return null;
        }
    }
}
