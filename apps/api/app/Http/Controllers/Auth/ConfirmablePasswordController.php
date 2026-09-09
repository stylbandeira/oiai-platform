<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ConfirmPasswordAction;
use App\Actions\Auth\ShowConfirmablePasswordAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class ConfirmablePasswordController extends Controller
{
    public function show(ShowConfirmablePasswordAction $action): Response
    {
        return $action->execute();
    }

    public function store(Request $request, ConfirmPasswordAction $action): RedirectResponse
    {
        return $action->execute($request);
    }
}
