<?php

namespace App\Policies;

use App\Models\CompanyOwners;
use App\Models\User;

class CompanyOwnersPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        } elseif ($user->isCompany()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CompanyOwners $companyOwners): bool
    {
        if ($user->isAdmin()) {
            return true;
        } elseif ($user->isCompany() && $companyOwners->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        } elseif ($user->isCompany()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CompanyOwners $companyOwners): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CompanyOwners $companyOwners): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CompanyOwners $companyOwners): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CompanyOwners $companyOwners): bool
    {
        return true;
    }
}
