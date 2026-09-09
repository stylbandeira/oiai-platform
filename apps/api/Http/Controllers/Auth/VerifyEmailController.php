<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\VerifyAuthenticatedEmailAction;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request, VerifyAuthenticatedEmailAction $action): RedirectResponse
    {
        return $action->execute($request);
    }
}
