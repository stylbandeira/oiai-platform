<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CreateNewPasswordAction;
use App\Actions\Auth\StoreNewPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\NewPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function create(Request $request, CreateNewPasswordAction $action): Response
    {
        return $action->execute($request);
    }

    public function store(NewPasswordRequest $request, StoreNewPasswordAction $action): RedirectResponse
    {
        return $action->execute($request->validated());
    }
}
