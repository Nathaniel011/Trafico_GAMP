<?php
namespace App\Http\Controllers;

use App\User;
use App\Services\AccountPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountPasswordController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function edit()
    {
        return response()->view('auth.change-password')->header('Cache-Control','private, no-store');
    }

    public function update(Request $request)
    {
        $data=$request->validate([
            'current_password'=>'required|string',
            'password'=>['required','string','min:8','max:72','confirmed','different:current_password',
                function ($attribute,$value,$fail) { if (is_string($value) && strlen($value)>72) $fail('La contraseña es demasiado larga. Utiliza una más corta.'); }],
        ],['password.min'=>'Usa al menos 8 caracteres.','password.confirmed'=>'Las contraseñas nuevas no coinciden.',
            'password.different'=>'La contraseña nueva debe ser diferente a la actual.']);
        $user=DB::transaction(function () use ($request,$data) {
            $user=User::lockForUpdate()->findOrFail($request->user()->id);
            AccountPasswords::verifyCurrent($user,$data['current_password']);
            AccountPasswords::replace($user,$data['password']);
            return $user;
        },3);
        Auth::guard('web')->setUser($user);
        $request->session()->regenerate(true);
        // Keep this session valid; AuthenticateSession rejects other sessions with the previous hash.
        $request->session()->put('password_hash_web',Auth::guard('web')->hashPasswordForCookie($user->password));
        return redirect()->route('account-password.edit')->with('success','Contraseña actualizada. Las otras sesiones deberán volver a ingresar.');
    }
}
