<?php

namespace Tests\Feature\Jobs;

use App\Jobs\GeocodeScheduleJob;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GeocodeScheduleJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocodes_pending_addresses_successfully(): void
    {
        Log::spy();
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '-8.047562',
                    'lon' => '-34.877001',
                ],
            ]),
        ]);

        $address = $this->createPendingAddress([
            'street' => 'Rua da Aurora',
            'number' => '100',
            'area' => 'Boa Vista',
            'city' => 'Recife',
            'state' => 'PE',
            'cep' => '50050000',
        ]);

        $this->runJob();

        $this->assertDatabaseHas('address', [
            'id' => $address->id,
            'latitude' => '-8.047562',
            'longitude' => '-34.877001',
            'geocode_status' => 'done',
            'geocode_error' => null,
        ]);

        $this->assertNotNull($address->fresh()->geocoded_at);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'nominatim.openstreetmap.org/search')
                && $request['q'] === 'Rua da Aurora, 100, Boa Vista, Recife, PE, 50050000, Brasil'
                && $request['format'] === 'json'
                && $request['limit'] === 1
                && $request['countrycodes'] === 'br'
                && $request['addressdetails'] === 1;
        });
    }

    public function test_marks_address_as_not_found_when_provider_returns_empty_result(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([]),
        ]);

        $address = $this->createPendingAddress();

        $this->runJob();

        $this->assertDatabaseHas('address', [
            'id' => $address->id,
            'latitude' => null,
            'longitude' => null,
            'geocode_status' => 'not_found',
            'geocode_error' => 'Endereço não encontrado',
        ]);
    }

    public function test_marks_address_as_not_found_when_provider_response_is_not_successful(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([], 500),
        ]);

        $address = $this->createPendingAddress();

        $this->runJob();

        $this->assertDatabaseHas('address', [
            'id' => $address->id,
            'geocode_status' => 'not_found',
            'geocode_error' => 'Endereço não encontrado',
        ]);
    }

    public function test_marks_address_as_error_when_request_throws_exception(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => fn() => throw new \RuntimeException('Provider unavailable'),
        ]);

        $address = $this->createPendingAddress();

        $this->runJob();

        $this->assertDatabaseHas('address', [
            'id' => $address->id,
            'geocode_status' => 'error',
            'geocode_error' => 'Provider unavailable',
        ]);
    }

    public function test_only_pending_addresses_without_coordinates_are_processed(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '-8.1',
                    'lon' => '-34.9',
                ],
            ]),
        ]);

        $pendingAddress = $this->createPendingAddress();
        $alreadyGeocoded = $this->createPendingAddress([
            'latitude' => -8.0,
            'longitude' => -34.0,
            'geocode_status' => 'pending',
        ]);
        $notPending = $this->createPendingAddress([
            'geocode_status' => 'done',
        ]);

        $this->runJob();

        Http::assertSentCount(1);
        $this->assertDatabaseHas('address', [
            'id' => $pendingAddress->id,
            'geocode_status' => 'done',
        ]);
        $this->assertDatabaseHas('address', [
            'id' => $alreadyGeocoded->id,
            'latitude' => -8.0,
            'longitude' => -34.0,
            'geocode_status' => 'pending',
        ]);
        $this->assertDatabaseHas('address', [
            'id' => $notPending->id,
            'geocode_status' => 'done',
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function test_processes_at_most_thirty_addresses_per_run(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '-8.1',
                    'lon' => '-34.9',
                ],
            ]),
        ]);

        collect(range(1, 31))->each(fn() => $this->createPendingAddress());

        $this->runJob();

        Http::assertSentCount(30);
        $this->assertSame(30, Address::where('geocode_status', 'done')->count());
        $this->assertSame(1, Address::where('geocode_status', 'pending')->count());
    }

    private function createPendingAddress(array $overrides = []): Address
    {
        return Address::factory()->create(array_merge([
            'street' => 'Rua Teste',
            'number' => '123',
            'area' => 'Centro',
            'city' => 'Recife',
            'state' => 'PE',
            'cep' => '50000000',
            'latitude' => null,
            'longitude' => null,
            'geocode_status' => 'pending',
            'geocode_error' => null,
            'geocoded_at' => null,
        ], $overrides));
    }

    private function runJob(): void
    {
        (new class extends GeocodeScheduleJob {
            protected function sleepSeconds(): int
            {
                return 0;
            }
        })->handle();
    }
}
