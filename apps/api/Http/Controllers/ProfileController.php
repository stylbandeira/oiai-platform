<?php

namespace App\Http\Controllers;

use App\Actions\Profile\DestroyProfileAction;
use App\Actions\Profile\EditProfileAction;
use App\Actions\Profile\UpdateProfileAction;
use App\Http\Requests\ProfileDestroyRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request, EditProfileAction $action): Response
    {
        return $action->execute($request);
    }

    public function update(ProfileUpdateRequest $request, UpdateProfileAction $action): RedirectResponse
    {
        $action->execute($request->user(), $request->validated());

        return Redirect::route('profile.edit');
    }

    public function destroy(ProfileDestroyRequest $request, DestroyProfileAction $action): RedirectResponse
    {
        return $action->execute($request);
    }
}
