<?php

namespace App\Repositories;

use App\Models\FavoriteProducts;
use App\Models\Product;
use App\Models\User;

class FavoriteProductsRepository
{
    protected FavoriteProducts $model;

    public function __construct(FavoriteProducts $model)
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

    public function findUserProductFavorite(User $user, Product $product)
    {
        return $this->model
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();
    }

    public function create(User $user, Product $product, array $data = [])
    {
        return $this->model->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            ...$data
        ]);
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
