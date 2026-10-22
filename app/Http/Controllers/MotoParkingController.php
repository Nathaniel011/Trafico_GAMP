<?php

namespace App\Http\Controllers;

use App\Models\MotoParking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;
use Yajra\DataTables\DataTables;

class MotoParkingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        return view('motoparking.index');
    }

    public function points(){
        return MotoParking::all();
    }

    public function data(){
        return DataTables::of(MotoParking::all())
            ->addColumn('actions', function ($moto) {
                $editRoute = route('motoparking.edit', $moto->id);
                return "<a href='$editRoute' class='btn btn-xs btn-primary'><i class='fa fa-edit'></i> Editar</a>";
            })
            ->editColumn('created_at', function($moto){
                $date = new Carbon($moto->created_at);
                return $date->format('d/m/Y');
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create(){
        $model = new MotoParking();

        $model->lat = -19.5892639;
        $model->lng = -65.7536236;

        return view('motoparking.create', [
            'model' => $model
        ]);
    }

    public function store(Request $request){

        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'zone' => 'required',
            'quantity' => 'numeric',
            'free' => 'numeric',
        ], [
            'required' => 'Requerido',
            'numeric' => 'Numérico',
        ]);

        $model = new MotoParking();
        $model->name = $request->name;
        $model->description = $request->description;
        $model->zone = $request->zone;
        $model->quantity = $request->quantity;
        $model->free = $request->free;
        $model->lng = $request->lng;
        $model->lat = $request->lat;
        $model->save();
        return redirect()->route('motoparking.index');
    }

    public function edit($id){
        $model = MotoParking::findOrFail($id);

        return view('motoparking.edit', [
            'model' => $model
        ]);
    }

    public function update(Request $request, $id){

        $request->validate([
            'name' => 'required',
            'description' => 'required',
        ], [
            'required' => 'Requerido',
        ]);

        $model = MotoParking::findOrFail($id);
        $model->name = $request->name;
        $model->description = $request->description;
        $model->zone = $request->zone;
        $model->quantity = $request->quantity;
        $model->free = $request->free;
        $model->lng = $request->lng;
        $model->lat = $request->lat;
        $model->save();
        return redirect()->route('motoparking.index');
    }

    public function show($id){
        $model = Certificate::findOrFail($id);
        return view('certificate.show', [
            'model' => $model
        ]);
    }
}
