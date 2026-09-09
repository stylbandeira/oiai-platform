<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UserRepository
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }


    public function paginate(array $filters, array $options = []): LengthAwarePaginator
    {
        $query = $this->filterQuery($filters, $options);

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }

    public function list(array $filters, array $options = []): Collection
    {
        $query = $this->filterQuery($filters, $options);

        return $query->get();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function findWithRelations(int $userId, array $relations)
    {
        return User::with($relations)
            ->findOrFail($userId);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
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

    /**
     * Add points to an user
     *
     * @param [type] $id
     * @param [type] $points
     * @return void
     */
    public function addPoints($id, $points)
    {
        $user = $this->find($id);
        return $this->update($id, [
            User::POINTS => $user->points + $points
        ]);
    }

    private function filterQuery(array $filters, array $options = []): Builder
    {
        $query = User::query();

        $filter = collect($filters);

        if ($filter->has('search') && !empty($filter->get('search'))) {
            $searchTerm = '%' . $filter->get('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('cpf', 'like', $searchTerm);
            });
        }

        if ($filter->has('status') && $filter->get('status') !== 'all') {
            $query->where('status', $filter->get('status'));
        }

        if ($filter->has('type') && $filter->get('type') !== 'all') {
            $query->where('type', $filter->get('type'));
        }

        $sortField = $filter->get('sort_by', 'created_at');
        $sortOrder = $filter->get('sort_order', 'desc');

        $validSortFields = ['name', 'points', 'reputation', 'created_at'];

        if (in_array($sortField, $validSortFields)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($options['with_trashed'] ?? false) {
            $query->withTrashed();
        }

        return $query;
    }

    public function getTopUsers(int $limit = 3)
    {
        return User::orderBy('points', 'desc')
            ->take($limit)
            ->get(['id', 'name', 'points', 'reputation']);
    }

    public function restore(User $user)
    {
        $user->restore();

        return $user;
    }
}
