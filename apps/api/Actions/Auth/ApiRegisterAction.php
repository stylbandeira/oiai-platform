<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class ApiRegisterAction
{
    public function execute(array $data)
    {
        $user = User::create([
            'type' => $data['user_type'],
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf' => $data['cpf'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        $user->sendEmailVerificationNotification();

        return $user;
    }
}
