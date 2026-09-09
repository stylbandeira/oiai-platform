<?php

namespace App\Repositories;

use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListRepository
{
    protected ItensList $list;

    public function __construct(ItensList $list)
    {
        $this->list = $list;
    }

    public function all()
    {
        return $this->list->all();
    }

    public function find($id)
    {
        return $this->list->findOrFail($id);
    }

    public function getCompletedProductIds(ItensList $list): array
    {
        return $list->listProducts()
            ->where('completed', true)
            ->pluck('product_id')
            ->toArray();
    }

    public function create(array $data): ItensList
    {
        return $this->list->create($data);
    }

    public function createForUser(User $user, array $data): ItensList
    {
        return $this->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'favorite' => false,
            'total' => 0,
        ]);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete(string $id)
    {
        return $this->list->destroy($id);
    }

    public function deleteIncompleteItems(ItensList $list)
    {
        return $list->listProducts()
            ->where('completed', false)
            ->delete();
    }

    private function filterQuery(): Builder
    {
        $query = ItensList::query();

        $query->with(['products', 'listProducts.product.unity', 'listProducts.product.category'])
            ->latest()
            ->orderByDesc('id');

        return $query;
    }

    public function loadDefaultRelations(ItensList $list)
    {
        $list->load([
            'listProducts.product.unity',
            'listProducts.product.category',
            'listProducts.companyProduct.company.address',
        ]);
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = $this->filterQuery();

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }

    public function userListsPaginated(int $id, array $filters)
    {
        $query = $this->filterQuery()
            ->where('user_id', $id);

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }

    /**
     * Attach products with quantities on a list
     *
     * @param ItensList $list
     * @param array $productsWithQuantities
     * @return void
     */
    public function attachProducts(ItensList $list, array $productsWithQuantities)
    {
        $list->products()->attach($productsWithQuantities);
    }
}
