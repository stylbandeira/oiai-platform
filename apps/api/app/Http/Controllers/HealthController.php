<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'application' => 'ok',
            'database' => $this->check(fn (): bool => (bool) DB::select('SELECT 1')),
            'queue' => $this->check(fn (): bool => Queue::size() >= 0),
            'meilisearch' => $this->check(function (): bool {
                $response = Http::timeout(2)->get(rtrim(config('scout.meilisearch.host'), '/').'/health');

                return $response->ok() && $response->json('status') === 'available';
            }),
        ];

        $healthy = ! in_array('error', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function check(callable $probe): string
    {
        try {
            return $probe() ? 'ok' : 'error';
        } catch (Throwable) {
            return 'error';
        }
    }
}
