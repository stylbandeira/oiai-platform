<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CreatePasswordResetLinkAction;
use App\Actions\Auth\SendPasswordResetLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(CreatePasswordResetLinkAction $action): Response
    {
        return $action->execute();
    }

    public function store(PasswordResetLinkRequest $request, SendPasswordResetLinkAction $action): RedirectResponse
    {
        return $action->execute($request->validated());
    }
}
