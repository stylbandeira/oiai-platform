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

    /** @var array{engine: string, duration_ms: int, fallback_used: bool}|null */
    private ?array $lastSearchTelemetry = null;

    public function __construct(
        Product $product,
        private ProductSearch $productSearch,
    ) {
        $this->product = $product;
    }

    public function all()
    {
        return $this->product->all();
    }

    public function list(User $user, array $data)
    {
        $this->lastSearchTelemetry = null;
        $query = $this->product->with(['category', 'unity', 'companies']);

        if ($user->isAdmin()) {
            $query->whereNull('products.deleted_at');
            $query->with(['pendingReviewSuggestions.suggestedProduct', 'pendingReviewSuggestions.submitter']);
        }
        $searchResultIds = null;

        if (isset($data['search']) && trim($data['search']) !== '') {
            $search = trim($data['search']);
            $started = hrtime(true);
            $searchResult = $this->productSearch->search(new ProductSearchCriteria(
                query: $search,
                categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
                brandId: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
                dimension: $data['quantity_dimension'] ?? null,
                page: isset($data['page']) ? (int) $data['page'] : 1,
                perPage: isset($data['per_page']) ? (int) $data['per_page'] : 20,
            ));
            $this->lastSearchTelemetry = [
                'engine' => $searchResult->engine,
                'duration_ms' => max(1, (int) round((hrtime(true) - $started) / 1_000_000)),
                'fallback_used' => $searchResult->fallbackUsed,
            ];
            $searchResultIds = $searchResult->ids;

            // A search term must never silently turn into an unfiltered listing.
            // This is especially important when an exact EAN does not exist.
            $query->whereIn('products.id', $searchResultIds);

            if ($searchResultIds !== []) {
                $quotedIds = implode(',', array_map('intval', $searchResultIds));
                $query->orderByRaw("FIELD(products.id, {$quotedIds}) DESC");
            }
        }

        $status = $data['status'] ?? $data['validated'] ?? null;
        if ($user->isAdmin() && $status !== null) {
            if ($status === 'pendentes') {
                $query->whereHas('pendingReviewSuggestions');
            } elseif ($status === 'validados') {
                $query->where('validated', true)->whereDoesntHave('pendingReviewSuggestions');
            } elseif ($status === 'invalidos') {
                $query->where('validated', false)->whereDoesntHave('pendingReviewSuggestions');
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

    public function statusCounts(): array
    {
        $pending = Product::query()->whereNull('products.deleted_at')->whereHas('pendingReviewSuggestions')->count();
        $validated = Product::query()->whereNull('products.deleted_at')->where('validated', true)->whereDoesntHave('pendingReviewSuggestions')->count();
        $invalid = Product::query()->whereNull('products.deleted_at')->where('validated', false)->whereDoesntHave('pendingReviewSuggestions')->count();

        return [
            'total' => $pending + $validated + $invalid,
            'pendentes' => $pending,
            'validados' => $validated,
            'invalidos' => $invalid,
        ];
    }

    /** @return array{engine: string, duration_ms: int, fallback_used: bool}|null */
    public function lastSearchTelemetry(): ?array
    {
        return $this->lastSearchTelemetry;
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

    public function validateProducts(array $productIds, int $userId, bool $validated): Collection
    {
        $changedIds = Product::query()->whereIn('id', $productIds)
            ->where('validated', ! $validated)
            ->pluck('id');

        if ($changedIds->isEmpty()) {
            return collect();
        }

        if ($validated) {
            Product::whereIn('id', $changedIds)->update([
                'validated' => true,
                'validated_by' => $userId,
                'refined' => ProductRefinementStatus::AdminValidated->value,
                'quantity_source' => ProductQuantitySource::AdminValidated->value,
                'quantity_confidence' => ProductQuantitySource::AdminValidated->confidence(),
            ]);
        } else {
            Product::whereIn('id', $changedIds)->update([
                'validated' => false,
                'validated_by' => null,
            ]);
        }

        return Product::whereIn('id', $changedIds)->get();
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
