<?php
namespace App\Http\Controllers;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
class UserAccessController extends Controller
{
    public function __construct() { $this->middleware(['auth', 'can:manage-system']); }
    public function index() { return view('casetas.access', ['users' => User::orderBy('name')->paginate(30)]); }
    public function update(Request $request, User $user)
    {
        $request->validate(['role' => ['required', Rule::in(['ADMIN', 'JEFE_UNIDAD', 'INSPECTOR_NOTIFICADOR', 'ASESOR_LEGAL'])]]);
        DB::transaction(function () use ($request, $user) {
            $users = User::lockForUpdate()->get();
            if (!in_array($request->role, ['ADMIN', 'JEFE_UNIDAD'], true) && !$users->contains(fn ($other) => $other->id !== $user->id && Gate::forUser($other)->allows('manage-system'))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['role' => 'Debe conservar al menos un administrador o jefe de unidad.']);
            }
            $user->roles = $request->role;
            $user->save();
        });
        return back()->with('success', 'Perfil actualizado. Los permisos se aplican también a las peticiones directas.');
    }
}
