<?php
namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountPasswords
{
    public static function verifyCurrent(User $user, string $password): void
    {
        if (!Hash::check($password,$user->password)) {
            throw ValidationException::withMessages(['current_password'=>'La contraseña actual no es correcta.']);
        }
    }

    public static function replace(User $user, string $password): void
    {
        $user->password=Hash::make($password);
        $user->remember_token=Str::random(60);
        $user->save();
    }
}
