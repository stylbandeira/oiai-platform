<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CreateAuthenticatedSessionAction;
use App\Actions\Auth\DestroyAuthenticatedSessionAction;
use App\Actions\Auth\StoreAuthenticatedSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(CreateAuthenticatedSessionAction $action): Response
    {
        return $action->execute();
    }

    public function store(LoginRequest $request, StoreAuthenticatedSessionAction $action): RedirectResponse
    {
        return $action->execute($request);
    }

    public function destroy(Request $request, DestroyAuthenticatedSessionAction $action): RedirectResponse
    {
        return $action->execute($request);
    }
}
