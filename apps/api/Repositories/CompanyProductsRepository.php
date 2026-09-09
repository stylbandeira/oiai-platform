<?php

namespace App\Repositories;

use App\Models\CompanyProducts;

class CompanyProductsRepository
{
    protected CompanyProducts $model;

    public function __construct(CompanyProducts $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function getByProductIdsWithPivots(array $productIds)
    {
        return $this->model
            ->whereIn('product_id', $productIds)
            ->with(['product', 'company.address'])
            ->get();
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }
}
