<?php

namespace App\Repositories;

use App\Contracts\Product\ProductSearch;
use App\DTO\Product\ProductSearchCriteria;
use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class ProductRepository
{
    protected Product $product;

    public function __construct(
        Product $product,
        private ProductSearch $productSearch,
    )
    {
        $this->product = $product;
    }

    public function all()
    {
        return $this->product->all();
    }

    public function list(User $user, array $data)
    {
        $query = $this->product->with(['category', 'unity', 'companies']);
        $searchResultIds = null;

        if (isset($data['search']) && trim($data['search']) !== '') {
            $search = trim($data['search']);
            $searchResult = $this->productSearch->search(new ProductSearchCriteria(
                query: $search,
                categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
                brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
                dimension: $data['quantity_dimension'] ?? null,
                page: isset($data['page']) ? (int) $data['page'] : 1,
                perPage: isset($data['per_page']) ? (int) $data['per_page'] : 20,
            ));
            $searchResultIds = $searchResult->ids;

            // A search term must never silently turn into an unfiltered listing.
            // This is especially important when an exact EAN does not exist.
            $query->whereIn('products.id', $searchResultIds);

            if ($searchResultIds !== []) {
                $quotedIds = implode(',', array_map('intval', $searchResultIds));
                $query->orderByRaw("FIELD(products.id, {$quotedIds}) DESC");
            }
        }

        if (isset($data['validated']) && ! $user->isClient()) {
            if ($data['validated'] === 'pendentes') {
                $query->where('validated', false);
            }

            if ($data['validated'] === 'validados') {
                $query->where('validated', true);
            }
        }

        if ($user->isClient()) {
            $query->where('validated', true)
                ->with(['userFavorites' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }]);
        }

        return $query->withCount('companies as sum_companies')
            ->orderBy('sum_companies', 'desc')
            ->orderBy('mentioned_quantity', 'desc')
            ->orderBy('listAdded', 'desc')
            ->orderBy('name', 'asc')
            ->limit(1500);
    }

    public function paginate(User $user, array $data)
    {
        return $this->list($user, $data)->paginate($data['per_page'] ?? 15);
    }

    public function find($id)
    {
        return $this->product->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->product->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);

        return $record;
    }

    public function validateProducts(array $productIds, int $userId): Collection
    {
        try {
            Product::whereIn('id', $productIds)
                ->where('validated', false)
                ->whereNull('validated_by')
                ->whereNotNull('created_by')
                ->update([
                    'validated' => true,
                    'validated_by' => $userId,
                    'refined' => ProductRefinementStatus::AdminValidated->value,
                    'quantity_source' => ProductQuantitySource::AdminValidated->value,
                    'quantity_confidence' => ProductQuantitySource::AdminValidated->confidence(),
                ]);
            $validatedProducts = Product::whereIn('id', $productIds)->get();
        } catch (\Throwable $th) {
            throw $th;
        }

        return $validatedProducts;
    }

    public function delete($id)
    {
        $product = $this->find($id);

        return $product->delete();
    }

    public function incrementListAdded(array $productsIds)
    {
        $this->product->whereIn('id', $productsIds)->increment('listAdded');
    }

    public function loadDefaultRelations(Product $product)
    {
        $product->with(['category', 'unity', 'companies']);
    }
}
