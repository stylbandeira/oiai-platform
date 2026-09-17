<?php

namespace App\Policies;

use App\Models\Unity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UsersPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return Response|bool
     */
    public function view(User $user, User $affected)
    {
        if ($user->isAdmin()) {
            return true;
        } elseif ($user->id === $affected->id) {
            return true;
        }

        return false;
    }

    public function dashboardData(User $user)
    {
        if (! $user->isClient()) {
            return false;
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
        if ($user->isAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  Unity  $unity
     * @return Response|bool
     */
    public function update(User $user, User $affected)
    {
        if ($user->isAdmin() && ! $affected->isAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  Unity  $unity
     * @return Response|bool
     */
    public function delete(User $user, User $affected)
    {
        if ($user->isAdmin() && ! $affected->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  Unity  $unity
     * @return Response|bool
     */
    public function restore(User $user, User $affected)
    {
        if ($user->isAdmin() && $affected->type !== 'admin') {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  Unity  $unity
     * @return Response|bool
     */
    public function forceDelete(User $user, User $affected)
    {
        if ($user->isAdmin() && $affected->type !== 'admin') {
            return true;
        }
    }
}
