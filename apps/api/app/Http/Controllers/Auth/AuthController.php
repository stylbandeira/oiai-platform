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
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function verifyEmail(int $id, string $hash, VerifyEmailAction $action)
    {
        return $action->execute($id, $hash);
    }

    public function login(ApiLoginRequest $request, ApiLoginAction $action, AuthAuditLogger $audit)
    {
        return $action->execute($request->validated(), $audit);
    }

    public function user(Request $request, GetAuthenticatedUserAction $action)
    {
        return $action->execute($request->user());
    }

    public function sendVerificationNotice(Request $request)
    {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail(),
        ]);
    }

    public function resendVerificationEmail(Request $request)
    {
        $user = $request->user();
        $alreadyVerified = $user->hasVerifiedEmail();

        Log::info('Verification email resend requested', [
            'user_id' => $user->id,
            'already_verified' => $alreadyVerified,
        ]);

        if (! $alreadyVerified) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Se existir uma conta associada a este endereço, enviaremos as instruções necessárias.',
        ], 202);
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
            'user' => (new UserResource($user))->withNotifications(),
            'access_token' => $user->createToken('auth_token', ['*'])->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }
}
