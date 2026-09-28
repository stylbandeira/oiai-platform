<?php

namespace App\Console\Commands;

use App\Services\Product\SearchInfrastructureMonitor;
use Illuminate\Console\Command;

final class SearchStatus extends Command
{
    protected $signature = 'products:search-status';

    protected $description = 'Exibe métricas das filas, normalização, indexação e Meilisearch';

    public function handle(SearchInfrastructureMonitor $monitor): int
    {
        $snapshot = $monitor->snapshot();
        $queue = $snapshot['queue'];
        $pipeline = $snapshot['pipeline'];
        $products = $snapshot['products'];
        $meilisearch = $snapshot['meilisearch'];

        $this->components->twoColumnDetail('Jobs pendentes', (string) $queue['pending']);
        $this->components->twoColumnDetail('Jobs em processamento', (string) $queue['reserved']);
        $this->components->twoColumnDetail('Jobs falhos', (string) $queue['failed']);
        $this->components->twoColumnDetail('Job pendente mais antigo', $this->valueOrUnavailable($queue['oldest_pending_seconds'], ' s'));
        $this->newLine();
        $this->components->twoColumnDetail('Normalização média (24h)', $this->valueOrUnavailable($pipeline['normalization_average_ms']));
        $this->components->twoColumnDetail('Indexação média (24h)', $this->valueOrUnavailable($pipeline['indexing_average_ms']));
        $this->components->twoColumnDetail('Falhas de normalização (24h)', (string) $pipeline['normalization_failures']);
        $this->components->twoColumnDetail('Falhas de indexação (24h)', (string) $pipeline['indexing_failures']);
        $this->newLine();
        $this->components->twoColumnDetail('Produtos no MySQL', (string) $products['total']);
        $this->components->twoColumnDetail('Produtos desatualizados', (string) $products['stale']);
        $this->components->twoColumnDetail('Versão atual do documento', (string) $products['document_version']);
        $this->newLine();

        if (! $meilisearch['available']) {
            $this->components->error('Meilisearch indisponível: '.$meilisearch['error']);
            $this->components->twoColumnDetail('Busca média Meilisearch (24h)', $this->valueOrUnavailable($meilisearch['average_search_ms']));

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('Meilisearch saudável', $meilisearch['healthy'] ? 'sim' : 'não');
        $this->components->twoColumnDetail('Documentos', (string) $meilisearch['documents']);
        $this->components->twoColumnDetail('Indexando agora', $meilisearch['is_indexing'] ? 'sim' : 'não');
        $this->components->twoColumnDetail('Tarefas pendentes', (string) $meilisearch['pending_tasks']);
        $this->components->twoColumnDetail('Tarefas falhas', (string) $meilisearch['failed_tasks']);
        $this->components->twoColumnDetail('Busca média Meilisearch (24h)', $this->valueOrUnavailable($meilisearch['average_search_ms']));
        $this->components->twoColumnDetail('Memória', $this->formatBytes($meilisearch['memory_bytes']));
        $this->components->twoColumnDetail('Disco reservado', $this->formatBytes($meilisearch['database_size_bytes']));
        $this->components->twoColumnDetail('Disco utilizado', $this->formatBytes($meilisearch['used_database_size_bytes']));

        if ($meilisearch['pending_tasks'] > 1000) {
            $this->newLine();
            $this->warn('Backlog crítico no Meilisearch: novas indexações podem demorar para ficar disponíveis.');
        }

        if ($meilisearch['recent_failed_tasks'] !== []) {
            $this->newLine();
            $this->warn('Últimas tarefas falhas do Meilisearch:');
            foreach ($meilisearch['recent_failed_tasks'] as $task) {
                $message = $task['error']['message'] ?? 'erro não informado';
                $this->line(sprintf('#%s %s: %s', $task['uid'] ?? '?', $task['type'] ?? 'task', $message));
            }
        }

        return self::SUCCESS;
    }

    private function valueOrUnavailable(mixed $value, string $suffix = ' ms'): string
    {
        return is_numeric($value) ? round((float) $value, 2).$suffix : 'sem dados';
    }

    private function formatBytes(mixed $bytes): string
    {
        if (! is_numeric($bytes)) {
            return 'indisponível';
        }

        $value = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;
        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return round($value, 2).' '.$units[$unit];
    }
}
