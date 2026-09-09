<?php

namespace App\Repositories;

use App\Models\ListProducts;

class ListProductsRepository
{
    protected ListProducts $model;

    public function __construct(ListProducts $model)
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

    public function createProductOnList(string $product_id, string $list_id, array $data)
    {
        ListProducts::create([
            'product_id' => $product_id,
            'list_id' => $list_id,
            ...$data
        ]);
    }

    public function update(string $id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function updateProductsOnList(array $product_ids, string $list_id, array $data)
    {
        ListProducts::where('list_id', $list_id)
            ->whereIn('product_id', $product_ids)
            ->update($data);
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }
}
