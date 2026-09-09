<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ImageFromUrl;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageFromUrlTest extends TestCase
{
    public function test_downloads_protected_image_with_provider_headers(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://oscbr.test/product/image' => Http::response(
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        $path = (new ImageFromUrl())->saveImageFromUrl(
            'https://oscbr.test/product/image',
            ['Authorization' => 'Bearer short-lived-token'],
        );

        Storage::disk('public')->assertExists($path);
        Http::assertSent(fn (Request $request) => $request->hasHeader(
            'Authorization',
            'Bearer short-lived-token',
        ));
    }
}
