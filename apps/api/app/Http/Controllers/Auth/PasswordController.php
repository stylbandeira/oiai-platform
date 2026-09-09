<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\UpdatePasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    public function update(PasswordUpdateRequest $request, UpdatePasswordAction $action): RedirectResponse
    {
        $action->execute($request->user(), $request->validated());

        return back();
    }
}
