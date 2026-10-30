<?php

namespace App\Http\Controllers;

use App\Models\MotoParking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;
use Yajra\DataTables\DataTables;

class MotoParkingController extends Controller
{
    public function __construct(){
        $this->middleware('can:field-work')->only(['create', 'store', 'edit', 'update', 'setState']);
        $this->middleware('auth');
    }

    public function index(){
        return view('motoparking.index');
    }

    public function points(){
        return MotoParking::all();
    }

    public function data(){
        return DataTables::of(MotoParking::query())
            ->addColumn('actions', function ($moto) {
                if (!auth()->user()->can('field-work')) { return ''; }
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



        return view('motoparking.create', [
            'model' => $model
        ]);
    }

    public function store(Request $request){
        $model = new MotoParking();
        \App\Services\ParkingLocation::apply($request, $model);
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
        $model = MotoParking::findOrFail($id);
        \App\Services\ParkingLocation::apply($request, $model);
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
