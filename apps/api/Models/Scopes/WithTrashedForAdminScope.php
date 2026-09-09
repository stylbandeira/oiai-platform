<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class WithTrashedForAdminScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (
            $user?->isAdmin() &&
            in_array(SoftDeletes::class, class_uses_recursive($model))
        ) {
            $builder->withTrashed();
        }
    }
}
