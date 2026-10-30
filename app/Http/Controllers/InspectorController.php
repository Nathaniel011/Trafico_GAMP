<?php

namespace App\Http\Controllers;

use App\Http\Resources\InspectorResource;
use App\Models\Inspector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InspectorController extends Controller
{
    public function __construct(){
        $this->middleware('can:manage-system')->only(['create', 'store', 'edit', 'update', 'delete']);
        $this->middleware('auth')->except(['apiLogin', 'apiProfile', 'apiLogout']);
    }

    public function index(){
        $inspectors = Inspector::all();
        return view('inspectors.index', [
            'list' => $inspectors
        ]);
    }

    public function show($id){
        return view('inspectors.show', [
            'model' => Inspector::findOrFail($id)
        ]);
    }

    public function create(){
        return view('inspectors.create', [
            'model' => new Inspector()
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:inspectors,username',
            'password' => 'required|confirmed'
        ], [
            'required' => 'Campo requerido',
            'confirmed' => 'Se requiere confirmacion'
        ]);
        $model = new Inspector();
        $model->name = $request->name;
        $model->username = $request->username;
        $model->password = bcrypt($request->password);
        $model->enabled = $request->boolean('enabled');
        $model->save();
        if (!$model->enabled) { $model->tokens()->delete(); }
        return redirect()->route('inspectors.index');
    }

    public function edit($id){
        $inspector = Inspector::findOrFail($id);
        return view('inspectors.edit', [
            'model' => $inspector
        ]);
    }

    public function update(Request $request, $id){
        $model = Inspector::findOrFail($id);
        $rules = [
            'name' => 'required',
            'username' => [
                'required',
                Rule::unique('inspectors')->ignore($model->id)
            ]
        ];
        $pass = (string) $request->get('password', '');
        $pass2 = (string) $request->get('password_confirmation', '');
        if(strlen($pass)>0 || strlen($pass2)>0){
            $rules['password'] = 'required|confirmed';
        }
        $request->validate($rules, [
            'required' => 'Campo requerido',
            'confirmed' => 'Se requiere confirmacion'
        ]);
        $model->name = $request->name;
        $model->username = $request->username;
        if(strlen($pass)>0 || strlen($pass2)>0){
            $model->password = bcrypt($request->password);
        }

        $model->enabled = $request->boolean('enabled');
        $model->save();
        if (!$model->enabled) { $model->tokens()->delete(); }

        return redirect()->route('inspectors.index');
    }

    public function apiLogin(Request $request){
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ], [
            'username.required' => 'Usuario requerido',
            'password.required' => 'Password requerido'
        ]);

        $inspector = Inspector::where('username', $request->username)->first();
        if(!$inspector || !$inspector->enabled || !Hash::check($request->password, $inspector->password)){
            throw ValidationException::withMessages([
                'username' => 'Credenciales incorrectas'
            ]);
        }

        return $inspector->createToken('inspector_token')->plainTextToken;
    }

    public function apiProfile(Request $request){
        $user = $request->user();
        return new InspectorResource($user);
    }

    public function apiLogout(Request $request){
        $request->user()->tokens()->delete();
        return response()->json(['success' => true]);
    }
}
