<?php

namespace Tests\Feature\Search;

use App\Jobs\IndexProductsBatchJob;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ReindexProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reindexes_selected_ids_in_a_single_batch(): void
    {
        Bus::fake();
        $products = Product::withoutEvents(fn () => Product::factory()->count(3)->create());

        $this->artisan('products:reindex', [
            '--id' => [(string) $products[0]->id, (string) $products[2]->id],
            '--chunk' => 50,
        ])->assertSuccessful();

        Bus::assertDispatchedTimes(IndexProductsBatchJob::class, 1);
        Bus::assertDispatched(
            IndexProductsBatchJob::class,
            function (IndexProductsBatchJob $job) use ($products): bool {
                $actual = $job->productIds;
                $expected = [(int) $products[0]->id, (int) $products[2]->id];
                sort($actual);
                sort($expected);

                return $actual === $expected;
            },
        );
    }

    public function test_reindexes_only_documents_older_than_requested_version(): void
    {
        Bus::fake();
        $stale = Product::withoutEvents(fn () => Product::factory()->create(['search_document_version' => 0]));
        Product::withoutEvents(fn () => Product::factory()->create([
            'search_document_version' => Product::SEARCH_DOCUMENT_VERSION,
            'search_indexed_at' => now(),
        ]));

        $this->artisan('products:reindex', ['--document-version' => Product::SEARCH_DOCUMENT_VERSION])
            ->assertSuccessful();

        Bus::assertDispatchedTimes(IndexProductsBatchJob::class, 1);
        Bus::assertDispatched(
            IndexProductsBatchJob::class,
            fn (IndexProductsBatchJob $job): bool => $job->productIds === [(int) $stale->id],
        );
    }

    public function test_rejects_partial_fresh_reindex(): void
    {
        Bus::fake();

        $this->artisan('products:reindex', ['--fresh' => true, '--id' => ['10']])
            ->assertExitCode(2);

        Bus::assertNotDispatched(IndexProductsBatchJob::class);
    }
}
