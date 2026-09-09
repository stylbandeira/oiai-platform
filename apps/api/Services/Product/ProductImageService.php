<?php

namespace App\Services\Product;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    public function replace(?string $currentPath, UploadedFile $image): string
    {
        if ($currentPath && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }

        return $image->store('companies/images', 'public');
    }
}
