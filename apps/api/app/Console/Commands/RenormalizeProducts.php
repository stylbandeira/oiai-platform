<?php

namespace App\Console\Commands;

use App\Jobs\NormalizeProductJob;
use App\Models\Product;
use Illuminate\Console\Command;

final class RenormalizeProducts extends Command
{
    protected $signature = 'products:renormalize {--version=2 : Normalization version to apply}';

    protected $description = 'Queue products whose normalization is older than the requested version';

    public function handle(): int
    {
        $version = (int) $this->option('version');
        $count = 0;

        Product::where('normalization_version', '<', $version)
            ->select('id')
            ->lazyById()
            ->each(function (Product $product) use (&$count): void {
                NormalizeProductJob::dispatch((int) $product->getKey());
                $count++;
            });

        $this->info("{$count} produto(s) enviado(s) para normalização.");

        return self::SUCCESS;
    }
}
