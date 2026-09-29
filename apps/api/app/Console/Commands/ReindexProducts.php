<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ReindexProducts extends Command
{
    protected $signature = 'products:reindex {--fresh : Apaga o índice antes de reconstruí-lo} {--chunk=500 : Quantidade de produtos por lote}';

    protected $description = 'Reconstrói o índice de produtos a partir do MySQL';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            Product::query()->get()->each(
                static fn (Product $product) => $product->unsearchable()
            );
        }

        $count = 0;
        Product::query()->chunkById((int) $this->option('chunk'), function ($products) use (&$count) {
            $products->searchable();
            $count += $products->count();
            $this->output->write("\rIndexados: {$count}");
        });

        $this->newLine();
        $this->info("Reindexação concluída: {$count} produtos.");

        return self::SUCCESS;
    }
}
