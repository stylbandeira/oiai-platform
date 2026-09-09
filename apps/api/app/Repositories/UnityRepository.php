<?php

namespace App\Repositories;

use App\Models\Unity;

class UnityRepository
{
    protected Unity $unity;

    public function __construct(Unity $unity)
    {
        $this->unity = $unity;
    }

    public function all()
    {
        return $this->unity->all();
    }

    public function list(array $filters)
    {
        $query = Unity::query();

        if (isset($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';

            $query->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', $searchTerm)
                    ->orWhere('abbreviation', 'like', $searchTerm);
            });
        }

        return $query;
    }

    public function paginate(array $filters)
    {
        return $this->list($filters)
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function find($id)
    {
        return $this->unity->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->unity->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        return $this->unity->destroy($id);
    }
}
