<?php

namespace App\Contracts\NFCe;

interface StateNFCeProvider
{
    public function supports(string $qrData): bool;

    public function scrapeFromQRCode(string $qrData): array;
}
