<?php

namespace Tests\Feature;

use App\Actions\Product\IndexProductAction;
use App\Models\SearchLog;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Services\Product\SearchQueryNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SearchLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_zero_result_searches_with_normalized_query(): void
    {
        $user = User::factory()->create();
        /** @var ProductRepository&MockInterface $repository */
        $repository = Mockery::mock(ProductRepository::class);
        $repository->shouldReceive('paginate')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 20));
        $repository->shouldReceive('lastSearchTelemetry')
            ->once()
            ->andReturn([
                'engine' => 'mysql',
                'duration_ms' => 12,
                'fallback_used' => true,
            ]);
        $action = new IndexProductAction($repository, new SearchQueryNormalizer);

        $action->execute($user, ['search' => '  NESCal   400 g  ', 'per_page' => 20]);

        $log = SearchLog::query()->sole();
        $this->assertSame('NESCal   400 g', $log->query);
        $this->assertSame('nescal 400g', $log->normalized_query);
        $this->assertSame(0, $log->result_count);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('mysql', $log->search_engine);
        $this->assertSame(12, $log->duration_ms);
        $this->assertTrue($log->fallback_used);
    }
}
