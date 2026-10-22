<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\UpdatePasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    public function update(PasswordUpdateRequest $request, UpdatePasswordAction $action, AuthAuditLogger $audit): RedirectResponse
    {
        $action->execute($request->user(), $request->validated(), $audit);

        return back();
    }
}
