<?php

namespace App\Repositories;

use App\Enums\ProductQuantitySource;
use App\Enums\ProductRefinementStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductRepository
{
    protected Product $product;

    public function __construct(Product $product)
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
            $searchResultIds = $this->searchProductIds($search);
            $likeTerm = '%'.$search.'%';
            $isExactCode = preg_match('/^\d{8,14}$/', $search) === 1;

            $query->where(function ($searchQuery) use ($search, $likeTerm, $isExactCode, $searchResultIds) {
                if ($isExactCode) {
                    $searchQuery->where('products.ean', $search)
                        ->orWhere('products.sku', $search);
                } else {
                    $searchQuery->where('products.name', 'like', $likeTerm)
                        ->orWhere('products.sku', 'like', $likeTerm);
                }

                if ($searchResultIds !== []) {
                    $searchQuery->orWhereIn('products.id', $searchResultIds);
                }
            });

            if ($searchResultIds !== []) {
                $quotedIds = implode(',', array_map('intval', $searchResultIds));
                $query->orderByRaw("FIELD(products.id, {$quotedIds}) DESC");
            }
        }

        if (isset($data['validated']) && !$user->isClient()) {
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

    /**
     * Search through Scout/Meilisearch while keeping the final result in an
     * Eloquent query so authorization, relations and pagination stay intact.
     */
    private function searchProductIds(string $search): array
    {
        $isExactCode = preg_match('/^\d{8,14}$/', $search) === 1;

        try {
            $builder = Product::search($isExactCode ? '' : $search);

            if ($isExactCode) {
                $builder->where('ean', $search);
            }

            return $builder->get()->pluck('id')->map(fn ($id) => (int) $id)->all();
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }
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
