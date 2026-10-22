<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function shops(Request $request){
        $start = $request->get('start', date('Y-m-d'));
        $end = $request->get('end', date('Y-m-d'));
        //fechas de inspección, propietario, lugar y actividad
        $data = Certificate::all();
        return view('reports.shops', [ 'data' => $data, 'start' => $start, 'end' => $end ]);
    }

}
