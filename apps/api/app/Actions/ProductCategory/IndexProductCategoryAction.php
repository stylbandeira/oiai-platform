<?php

namespace App\Actions\ProductCategory;

use App\Http\Resources\CategoryResource;
use App\Repositories\ProductCategoryRepository;

class IndexProductCategoryAction
{
    public function __construct(
        private ProductCategoryRepository $productCategoryRepository,
    ) {}

    public function execute()
    {
        return CategoryResource::collection($this->productCategoryRepository->all());
    }
}
