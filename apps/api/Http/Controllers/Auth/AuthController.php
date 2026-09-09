<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ApiLoginAction;
use App\Actions\Auth\ApiRegisterAction;
use App\Actions\Auth\GetAuthenticatedUserAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\NoticeAction;
use App\Actions\Auth\VerifyEmailAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Requests\Auth\ApiRegisterRequest;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function verifyEmail(Int $id, String $hash, VerifyEmailAction $action)
    {
        return $action->execute($id, $hash);
    }

    public function login(ApiLoginRequest $request, ApiLoginAction $action)
    {
        return $action->execute($request->validated());
    }

    public function user(Request $request, GetAuthenticatedUserAction $action)
    {
        return $action->execute($request->user());
    }

    public function logout(Request $request, LogoutAction $action)
    {
        return $action->execute($request);
    }

    public function notice(NoticeAction $action)
    {
        return $action->execute();
    }

    public function register(ApiRegisterRequest $request, ApiRegisterAction $action)
    {
        $user = $action->execute($request->validated());

        return response([
            'user' => $user,
            'access_token' => $user->createToken('auth_token', ['*'])->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }
}
