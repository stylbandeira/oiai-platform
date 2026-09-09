<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\PromptEmailVerificationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request, PromptEmailVerificationAction $action): RedirectResponse|Response
    {
        return $action->execute($request);
    }
}
