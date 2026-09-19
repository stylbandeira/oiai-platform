<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AddressPolicy
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
    public function view(User $user, Address $address)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create(User $user)
    {
        if ($user->isAdmin() || $user->isCompany()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, Address $address)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, Address $address)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, Address $address)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, Address $address)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }
}
