<?php

namespace App\Http\Controllers;

use App\Models\CarParking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;
use Yajra\DataTables\DataTables;

class CarParkingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        return view('carparking.index');
    }

    public function points(){
        return CarParking::all();
    }

    public function data(){
        return DataTables::of(carparking::all())
            ->addColumn('actions', function ($car) {
                $editRoute = route('carparking.edit', $car->id);
                return "<a href='$editRoute' class='btn btn-xs btn-primary'><i class='fa fa-edit'></i> Editar</a>";
            })
            ->editColumn('created_at', function($car){
                $date = new Carbon($car->created_at);
                return $date->format('d/m/Y');
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create(){
        $model = new CarParking();

        $model->lat = -19.5892639;
        $model->lng = -65.7536236;

        return view('carparking.create', [
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

        $model = new CarParking();
        $model->name = $request->name;
        $model->description = $request->description;
        $model->zone = $request->zone;
        $model->quantity = $request->quantity;
        $model->free = $request->free;
        $model->lng = $request->lng;
        $model->lat = $request->lat;
        $model->save();
        return redirect()->route('carparking.index');
    }

    public function edit($id){
        $model = CarParking::findOrFail($id);

        return view('carparking.edit', [
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

        $model = CarParking::findOrFail($id);
        $model->name = $request->name;
        $model->description = $request->description;
        $model->zone = $request->zone;
        $model->quantity = $request->quantity;
        $model->free = $request->free;
        $model->lng = $request->lng;
        $model->lat = $request->lat;
        $model->save();
        return redirect()->route('carparking.index');
    }

    public function show($id){
        $model = Certificate::findOrFail($id);
        return view('certificate.show', [
            'model' => $model
        ]);
    }
}
