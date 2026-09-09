<?php

namespace App\Repositories;

use App\Models\ProductCategory;

class ProductCategoryRepository
{
    protected ProductCategory $productCategory;

    public function __construct(ProductCategory $productCategory)
    {
        $this->productCategory = $productCategory;
    }

    public function all()
    {
        return $this->productCategory->all();
    }

    public function find($id)
    {
        return $this->productCategory->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->productCategory->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function firstOrNew(string $name)
    {
        ProductCategory::firstOrNew([
            'name' => $name
        ]);
    }

    public function delete($id)
    {
        return $this->productCategory->destroy($id);
    }
}
