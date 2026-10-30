<?php

namespace App\Http\Controllers;

use App\Http\Resources\GroupResource;
use App\Http\Resources\RouteResource;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(){
        $this->middleware('can:manage-system')->only(['create', 'store', 'edit', 'update', 'delete']);
        $this->middleware('auth')->except(['apiIndex', 'apiRoutes']);
    }

    public function apiIndex(){
        $groups = Group::orderBy('name', 'asc');
        return GroupResource::collection($groups->get());
    }

    public function apiRoutes($id){
        $group = Group::findOrFail($id);
        $routes = $group->routes()->where('enabled', true);
        return RouteResource::collection($routes->get());
    }

    public function index(){
        $groups = Group::all();
        return view('groups.index', [
            'list' => $groups
        ]);
    }

    public function show($id){
        $group = Group::findOrFail($id);
        return view('groups.show', [
            'model' => $group
        ]);
    }

    public function create(){
        return view('groups.create', [
            'model' => new Group()
        ]);
    }

    public function store(Request $request){
        $this->saveGroup($request, new Group());
        return redirect()->route('groups.index');
    }

    public function edit($id){
        $model = Group::findOrFail($id);
        return view('groups.edit', [
            'model' => $model
        ]);
    }

    public function update(Request $request, $id){
        $this->saveGroup($request, Group::findOrFail($id));
        return redirect()->route('groups.index');
    }

    private function saveGroup(Request $request, Group $model): void
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'year' => 'nullable|integer|between:1900,'.now('America/La_Paz')->year,
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'required' => 'Requerido',
            'numeric' => 'Solo se permiten números',
            'between' => 'Valor no permitido'
        ]);

        $model->name = $validated['name'];
        $model->description = $validated['description'] ?? null;
        $year = $validated['year'] ?? 0;
        if($year>0){
            $date = Carbon::createFromFormat('Y', $year);
            $date->startOfYear();
            $model->creation_date = $date->toDateString();
        } else {
            $model->creation_date = null;
        }

        if($request->hasFile('image')){
            $path = $request->file('image')->store('img/group_logos', 'public');
            $model->logo_path = $path;
        }

        $model->save();
    }
}
