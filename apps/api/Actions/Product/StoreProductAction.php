<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\CompanyProducts;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CompanyProductsRepository;
use App\Repositories\ProductRepository;

class StoreProductAction
{
    public function __construct(
        private CompanyProductsRepository $company_products_repository,
        private ProductRepository $product_repository,

    ) {}

    public function execute(User $user, ProductStoreRequest $request): Product
    {
        $validatedData = $request->validated();
        $validatedData['created_by'] = $user->id;

        if ($request->hasFile('img')) {
            $imgPath = $request->file('img')->store('products/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        if ($user->isClient()) {
            $validatedData['validated'] = false;
        }

        $product = $this->product_repository->create($validatedData);

        if ($user->isCompany()) {
            $this->company_products_repository->create([
                'product_id' => $product->id,
                'company_id' => $request->company_id,
                'average_price' => $validatedData['average_price'] ?? null,
            ]);
        }

        $this->product_repository->loadDefaultRelations($product);

        return $product;
    }
}
