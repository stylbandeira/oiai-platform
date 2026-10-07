<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StoreRegisteredUserAction
{
    public function execute(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'type' => $data['type'],
        ]);

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
