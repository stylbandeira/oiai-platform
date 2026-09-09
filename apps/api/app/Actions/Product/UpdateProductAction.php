<?php

namespace App\Actions\Product;

use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Services\Product\ProductImageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;

class UpdateProductAction
{
    public function __construct(
        private ProductRepository $product_repository,
        private ProductImageService $product_image_service,
    ) {}

    public function execute(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        if ($image) {
            $data['img'] = $this->product_image_service->replace(
                $product->img,
                $image
            );
        }

        $this->product_repository->update($product->id, $data);
        $this->product_repository->loadDefaultRelations($product);

        return $product->refresh();
    }
}
