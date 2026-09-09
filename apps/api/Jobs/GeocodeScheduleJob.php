<?php

namespace App\Jobs;

use App\Models\Address;
use App\Services\Geolocation\GeolocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeScheduleJob implements ShouldQueue
{
    use Queueable;

    private int $limit;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->limit = 30;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $addresses = Address::query()
            ->whereNull('latitude')
            ->whereNull('longitude')
            ->where('geocode_status', 'pending')
            ->limit((int) $this->limit)
            ->get();

        foreach ($addresses as $address) {
            $full_address = collect([
                $address->street,
                $address->number,
                $address->area,
                $address->city,
                $address->state,
                $address->cep,
                'Brasil',
            ])->filter()->implode(', ');

            $geolocation_service = new GeolocationService();
            $address_data = $geolocation_service->search($full_address);

            $address->update($address_data);

            sleep($this->sleepSeconds()); // respeita o limite do Nominatim público
        }

        Log::alert([
            'Message' => count($addresses) . ' endereços processados'
        ]);
    }

    protected function sleepSeconds(): int
    {
        return 1;
    }
}
