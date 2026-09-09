<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CreateRegisteredUserAction;
use App\Actions\Auth\StoreRegisteredUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisteredUserStoreRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(CreateRegisteredUserAction $action): Response
    {
        return $action->execute();
    }

    public function store(RegisteredUserStoreRequest $request, StoreRegisteredUserAction $action): RedirectResponse
    {
        return $action->execute($request->validated());
    }
}
