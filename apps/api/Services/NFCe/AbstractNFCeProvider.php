<?php

namespace App\Services\NFCe;

use App\Contracts\NFCe\StateNFCeProvider;
use GuzzleHttp\Client;

abstract class AbstractNFCeProvider implements StateNFCeProvider
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'connect_timeout' => 5,
            'timeout' => 20,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);
    }
}
