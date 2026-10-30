<?php
namespace App\Http\Controllers;

use App\User;
use App\Services\UserRoleRules;
use App\Services\AccountPasswords;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserAccessController extends Controller
{
    private const ROLES = ['ADMIN'=>'Administrador', 'JEFE_UNIDAD'=>'Jefe de Unidad',
        'INSPECTOR_NOTIFICADOR'=>'Inspector-Notificador', 'ASESOR_LEGAL'=>'Asesor Legal'];

    public function __construct() { $this->middleware(['auth', 'can:manage-system']); }

    public function index()
    {
        return response()->view('casetas.access', ['users'=>User::orderBy('name')->paginate(30), 'roles'=>self::ROLES])
            ->header('Cache-Control','private, no-store');
    }

    public function create()
    {
        return view('casetas.user-form', ['user'=>new User(), 'roles'=>$this->roleOptions()]);
    }

    public function edit(User $user)
    {
        return view('casetas.user-form', ['user'=>$user, 'roles'=>$this->roleOptions($user)]);
    }

    private function roleOptions(?User $target = null): array
    {
        $admins=UserRoleRules::administrators(User::get(['id','roles']));
        if ($target && $admins->count() === 1 && $admins->first()->id === $target->id) return ['ADMIN'=>self::ROLES['ADMIN']];
        $roles=self::ROLES;
        if ($admins->contains(fn ($admin)=>$admin->id !== $target?->id)) unset($roles['ADMIN']);
        return $roles;
    }
    private function validateIdentity(Request $request, ?User $user = null): array
    {
        $normalized = [];
        foreach (['name','ci','email'] as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $value = trim($value);
                if ($field === 'ci') $value = mb_strtoupper(Str::squish($value));
                if ($field === 'email') $value = mb_strtolower($value);
            }
            $normalized[$field] = $value;
        }
        $request->merge($normalized);
        return $request->validate([
            'name'=>'required|string|max:255',
            'ci'=>['required','string','max:80',Rule::unique('users','ci')->ignore($user?->id)],
            'email'=>['required','email','max:254',Rule::unique('users','email')->ignore($user?->id)],
            'role'=>['required',Rule::in(array_keys(self::ROLES))],
        ], ['ci.unique'=>'Este CI ya tiene una cuenta.', 'email.unique'=>'Este correo ya tiene una cuenta.']);
    }

    public function store(Request $request)
    {
        $data = $this->validateIdentity($request);
        $password = Str::password(16);
        try {
            $user = DB::transaction(function () use ($data, $password) {
                // Serialize usernames for registrations with matching names; the unique index is the final guard.
                $users=UserRoleRules::lockUsers();
                UserRoleRules::assertAssignment($users,$data['role']);
                $base = substr(Str::slug($data['name'], '.'), 0, 65) ?: 'usuario';
                $username = $base;
                for ($number=2; User::whereRaw('LOWER(username) = ?', [$username])->exists(); $number++) {
                    $username = $base.'.'.$number;
                }
                $user = new User();
                $user->name = $data['name'];
                $user->ci = $data['ci'];
                $user->email = $data['email'];
                $user->roles = $data['role'];
                $user->username = $username;
                $user->password = Hash::make($password);
                $user->save();
                return $user;
            }, 3);
        } catch (QueryException $exception) {
            $errors=[];
            foreach (['ci','email'] as $field) {
                if (User::where($field, $data[$field])->exists()) $errors[$field]='Este dato ya tiene una cuenta. Revise el listado.';
            }
            if ($errors) throw ValidationException::withMessages($errors);
            throw $exception;
        }
        return redirect()->route('user-access.index')->with('success','Cuenta creada correctamente.')
            ->with('created_user_credentials',['username'=>$user->username,'password'=>$password]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate(['role'=>['required',Rule::in(array_keys(self::ROLES))]]);
        // Preserve existing role-only submissions. The full form also updates the three identity fields.
        $data = $request->hasAny(['name','ci','email']) ? $this->validateIdentity($request,$user) : ['role'=>$request->role];
        DB::transaction(function () use ($data, $user) {
            $users = UserRoleRules::lockUsers();
            UserRoleRules::assertAssignment($users,$data['role'],$user->id);
            if (!in_array($data['role'], ['ADMIN','JEFE_UNIDAD'], true)
                && !$users->contains(fn ($other)=>$other->id !== $user->id && Gate::forUser($other)->allows('manage-system'))) {
                throw ValidationException::withMessages(['role'=>'Debe conservar al menos un administrador o jefe de unidad.']);
            }
            $user->roles = $data['role'];
            foreach (['name','ci','email'] as $field) if (array_key_exists($field,$data)) $user->$field=$data[$field];
            $user->save();
        });
        return redirect()->route('user-access.index')->with('success','Datos y perfil actualizados.');
    }
    public function resetForm(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) return redirect()->route('account-password.edit');
        return response()->view('casetas.reset-password',['user'=>$user])->header('Cache-Control','private, no-store');
    }

    public function resetPassword(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) throw ValidationException::withMessages(['user'=>'Para tu propia cuenta utiliza Cambiar mi contraseña.']);
        $password=Str::password(16);
        DB::transaction(function () use ($request,$user,$password) {
            $accounts=User::whereIn('id',[$request->user()->id,$user->id])->orderBy('id')->lockForUpdate()->get();
            $actor=$accounts->firstWhere('id',$request->user()->id);
            abort_unless($actor && Gate::forUser($actor)->allows('manage-system'),403);
            $target=$accounts->firstWhere('id',$user->id);
            abort_unless($target,404);
            if (UserRoleRules::isAdministrator($target->roles)) {
                $data=$request->validate(['current_password'=>'required|string']);
                AccountPasswords::verifyCurrent($actor,$data['current_password']);
            }
            AccountPasswords::replace($target,$password);
        },3);
        return redirect()->route('user-access.index')->with('success','Contraseña restablecida. Entrega la nueva contraseña al titular.')
            ->with('credentials_title','Contraseña restablecida')
            ->with('created_user_credentials',['username'=>$user->username,'password'=>$password]);
    }
}