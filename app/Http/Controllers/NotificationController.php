<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('apiIndex');
    }

    public function index(){
        $list = Notification::all();
        return view('notification.index', [
            'list' => $list
        ]);
    }

    public function show($id){
        $model = Notification::findOrFail($id);
        return view('notification.show', [
            'model' => $model
        ]);
    }

    public function create(){
        $model = new Notification();
        return view('notification.create', [
            'model' => $model
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'title' => 'required',
            'body' => 'required',
            'level' => 'required',
        ], [
            'required' => 'Requerido'
        ]);

        $model = new Notification();
        $model->title = $request->title;
        $model->body = $request->body;
        $model->summary = $request->body;
        $model->level = $request->level;
        $model->save();
        return redirect()->route('notifications.index');
    }

    public function edit($id){
        $model = Notification::find($id);
        return view('notification.edit', [
            'model' => $model
        ]);
    }

    public function update(Request $request, $id){
        $request->validate([
            'title' => 'required',
            'body' => 'required',
            'level' => 'required',
        ], [
            'required' => 'Requerido'
        ]);

        $model = Notification::find($id);
        $model->title = $request->title;
        $model->body = $request->body;
        $model->summary = $request->body;
        $model->level = $request->level;
        $model->save();
        return redirect()->route('notifications.index');
    }

    public function delete($id){
        $model = Notification::find($id);
        $model->delete();
        return redirect()->route('notifications.index');
    }

    public function apiIndex(){
        $items = Notification::orderBy('created_at', 'desc')->get();
        return NotificationResource::collection($items);
    }
}
