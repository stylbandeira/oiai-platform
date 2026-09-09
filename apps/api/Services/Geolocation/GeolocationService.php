<?php

namespace App\Services\Geolocation;

use App\Models\Address;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeolocationService
{
    public function search(string $address): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => sprintf(
                    '%s/%s (%s)',
                    config('app.name'),
                    config('app.version'),
                    config('services.nominatim.email'),
                ),
            ])->get(config('services.nominatim.url'), [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'br',
                'addressdetails' => 1,
            ]);

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];

                return [
                    'latitude' => $result['lat'],
                    'longitude' => $result['lon'],
                    'geocode_status' => 'done',
                    'geocode_error' => null,
                    'geocoded_at' => now(),
                ];
            } else {
                return [
                    'geocode_status' => 'not_found',
                    'geocode_error' => 'Endereço não encontrado',
                ];
            }
        } catch (\Throwable $e) {
            return [
                'geocode_status' => 'error',
                'geocode_error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Returns distance between two points using Haversine formula
     *
     * @param array $origin
     * @param array $destination
     * @return float|null
     */
    public function between(array $origin, array $destination): ?float
    {
        if (
            $origin['latitude'] === null ||
            $origin['longitude'] === null ||
            $destination['latitude'] === null ||
            $destination['longitude'] === null
        ) {
            return null;
        }

        $earthRadiusKm = 6371;

        $latFrom = deg2rad((float) $origin['latitude']);
        $lonFrom = deg2rad((float) $origin['longitude']);
        $latTo = deg2rad((float) $destination['latitude']);
        $lonTo = deg2rad((float) $destination['longitude']);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo)
            * sin($lonDelta / 2) ** 2;

        $distance = $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($distance, 3);
    }
}
