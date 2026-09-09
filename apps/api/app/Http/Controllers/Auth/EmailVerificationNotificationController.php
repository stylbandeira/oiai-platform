<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendEmailVerificationNotificationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request, SendEmailVerificationNotificationAction $action): RedirectResponse
    {
        return $action->execute($request);
    }
}
