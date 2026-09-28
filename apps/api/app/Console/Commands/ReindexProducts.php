<?php

namespace App\Console\Commands;

use App\Jobs\IndexProductsBatchJob;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

final class ReindexProducts extends Command
{
    protected $signature = 'products:reindex
        {--fresh : Remove todos os documentos antes da reconstrução completa}
        {--chunk=500 : Quantidade de produtos lidos do MySQL por lote}
        {--id=* : Reindexa somente os IDs informados; pode ser repetido}
        {--document-version= : Reindexa documentos anteriores à versão informada}';

    protected $description = 'Enfileira a reconstrução controlada do índice de produtos a partir do MySQL';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) $this->option('id')),
            static fn (int $id): bool => $id > 0,
        )));
        $versionOption = $this->option('document-version');
        $version = $versionOption === null ? Product::SEARCH_DOCUMENT_VERSION : (int) $versionOption;

        if ($chunk < 1) {
            $this->error('--chunk deve ser maior que zero.');

            return self::INVALID;
        }

        if ($version < 1 || $version > Product::SEARCH_DOCUMENT_VERSION) {
            $this->error('--document-version deve estar entre 1 e '.Product::SEARCH_DOCUMENT_VERSION.'.');

            return self::INVALID;
        }

        if ($this->option('fresh') && ($ids !== [] || $versionOption !== null)) {
            $this->error('--fresh só pode ser usado em uma reconstrução completa.');

            return self::INVALID;
        }

        if ($this->option('fresh') && ! $this->flushIndex()) {
            return self::FAILURE;
        }

        $query = Product::query()->select('id');
        $this->applyScope($query, $ids, $versionOption !== null, $version);

        $count = 0;
        $query->chunkById($chunk, function (Collection $products) use (&$count, $version): void {
            $ids = $products->modelKeys();
            IndexProductsBatchJob::dispatch($ids, $version);
            $count += count($ids);

            $this->output->write("\rEnfileirados: {$count}");
        });

        $this->newLine();
        $this->info("Reindexação enfileirada: {$count} produto(s), versão {$version}.");

        return self::SUCCESS;
    }

    /** @param list<int> $ids */
    private function applyScope(Builder $query, array $ids, bool $byVersion, int $version): void
    {
        if ($ids !== []) {
            $query->whereKey($ids);
        }

        if ($byVersion) {
            $query->where('search_document_version', '<', $version);
        }
    }

    private function flushIndex(): bool
    {
        try {
            Product::removeAllFromSearch();
            Product::query()->update([
                'search_document_version' => 0,
                'search_indexed_at' => null,
            ]);

            $this->info('Limpeza do índice aceita pelo Meilisearch.');

            return true;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Não foi possível limpar o índice: '.$exception->getMessage());

            return false;
        }
    }
}
