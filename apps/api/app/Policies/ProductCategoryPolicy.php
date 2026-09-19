<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProductCategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return Response|bool
     */
    public function view(User $user, ProductCategory $productCategory)
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
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return Response|bool
     */
    public function update(User $user, ProductCategory $productCategory)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return Response|bool
     */
    public function delete(User $user, ProductCategory $productCategory)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return Response|bool
     */
    public function restore(User $user, ProductCategory $productCategory)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return Response|bool
     */
    public function forceDelete(User $user, ProductCategory $productCategory)
    {
        return $user->isAdmin();
    }
}
