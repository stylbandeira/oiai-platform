<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return Response|bool
     */
    public function view(User $user, Company $company)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isCompany()) {
            return $user->ownsActiveCompany($company)
                && $company->isActive();
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user)
    {
        return in_array($user->type, ['admin', 'company']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, Company $company)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, Company $company)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, Company $company)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, Company $company)
    {
        return false;
    }
}
