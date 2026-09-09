<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyRepository
{
    protected Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function all()
    {
        return $this->company->all();
    }

    public function find($id)
    {
        $query = Company::query()
            ->where('id', $id);

        $user = User::find(Auth::user()->id);

        if (!$user->isAdmin()) {
            $query->whereNot('status', Company::STATUS_INACTIVE);
        }

        return $query
            ->findOrFail($id);
    }

    public function findWithRelationships(int $companyId, array $relationships)
    {
        return Company::with($relationships)
            ->findOrFail($companyId);
    }

    public function create(array $data)
    {
        return $this->company->create($data);
    }

    public function firstOrCreateByCnpj(array $data): Company
    {
        return Company::firstOrCreate(
            [
                'cnpj' => $data['cnpj'],
            ],
            [
                ...$data,
                'status' => Company::STATUS_PENDING,
            ]
        );
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        return $this->company->destroy($id);
    }

    public function list(Request $request)
    {
        $query = Company::query();

        if ($request->has('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('cnpj', 'like', $searchTerm);
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->user()->isAdmin()) {

            $query->withTrashed();

            //TO-DO - QUERO DEIXAR ISSO MAIS AUTOMÁTICO
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
        }

        return $query;
    }

    public function paginateForUser(User $user, int $perPage = 10)
    {
        return Company::query()
            ->with(['owners', 'products'])
            ->when(
                $user->isCompany(),
                fn($query) => $query->ownedByActiveUser($user)
            )
            ->withOwnerRelationshipFor($user)
            ->withCount('products')
            ->paginate($perPage ?? 10);
    }
}
