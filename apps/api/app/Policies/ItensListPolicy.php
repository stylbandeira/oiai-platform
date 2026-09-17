<?php

namespace App\Policies;

use App\Models\ItensList;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ItensListPolicy
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
    public function view(User $user, ItensList $itensList)
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
        if ($user->isAdmin()) {
            return false;
        } elseif ($user->isClient()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, ItensList $itensList)
    {
        if ($user->isAdmin()) {
            return true;
        } elseif ($user->isClient() && $itensList->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, ItensList $itensList)
    {
        if ($user->isAdmin()) {
            return true;
        } elseif ($user->isClient() && $itensList->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, ItensList $itensList)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, ItensList $itensList)
    {
        if ($user->isAdmin()) {
            return true;
        } else {
            return true;
        }
    }
}
