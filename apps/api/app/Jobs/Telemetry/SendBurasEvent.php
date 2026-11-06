<?php

namespace App\Jobs\Telemetry;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendBurasEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3;

    /** @param array<string, mixed> $event */
    public function __construct(public array $event) {}

    public function handle(): void
    {
        $url = rtrim((string) config('services.buras.url'), '/');
        $token = (string) config('services.buras.token');
        if ($url === '' || $token === '') {
            return;
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout(0.5)
                ->timeout(1)
                ->post($url.'/v1/events', $this->event);

            if ($response->successful() === false) {
                Log::warning('Buras delivery unavailable.');
            }
        } catch (Throwable) {
            // The structured local event remains available when Buras is down.
            Log::warning('Buras delivery unavailable.');
        }
    }
}
