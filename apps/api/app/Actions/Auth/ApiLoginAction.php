<?php

namespace App\Actions\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Support\Facades\Hash;

class ApiLoginAction
{
    public function execute(array $data, AuthAuditLogger $audit)
    {
        $user = User::where('email', $data['email'])
            ->where('type', $data['user_type'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            $audit->record('login', 'failure', $user);

            return response()->json([
                'message' => 'Credenciais inválidas',
            ], 401);
        }

        $audit->record('login', 'success', $user);

        return response([
            'user' => (new UserResource($user))->withNotifications(),
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }
}
