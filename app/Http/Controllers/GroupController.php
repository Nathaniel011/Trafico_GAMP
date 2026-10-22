<?php

namespace App\Http\Controllers;

use App\Http\Resources\GroupResource;
use App\Http\Resources\RouteResource;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['apiIndex', 'apiRoutes']);
    }

    public function apiIndex(){
        $groups = Group::orderBy('name', 'asc');
        return GroupResource::collection($groups->get());
    }

    public function apiRoutes($id){
        $group = Group::find($id);
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

    public function edit($id){
        $model = Group::findOrFail($id);
        return view('groups.edit', [
            'model' => $model
        ]);
    }

    public function update(Request $request, $id){

        $request->validate([
            'name' => 'required',
            'year' => 'nullable|numeric|between:1900,2023',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'required' => 'Requerido',
            'numeric' => 'Solo se permiten números',
            'between' => 'Valor no permitido'
        ]);

        $model = Group::findOrFail($id);
        $model->name = $request->name;
        $model->description = $request->description;
        $year = $request->get('year', 0);
        if($year>0){
            $date = Carbon::createFromFormat('Y', $year);
            $date->startOfYear();
            $model->creation_date = $date;
        }

        if($request->hasFile('image')){
            $path = $request->file('image')->store('img/group_logos', 'public');
            $model->logo_path = $path;
        }

        $model->save();
        return redirect()->route('groups.index');
    }
}
