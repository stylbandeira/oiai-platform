<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ApiLoginAction
{
    public function execute(array $data)
    {
        $user = User::where('email', $data['email'])
            ->where('type', $data['user_type'])
            ->first();

        if (!Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas',
            ], 401);
        }

        return response([
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }
}
