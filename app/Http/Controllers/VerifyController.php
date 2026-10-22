<?php

namespace App\Http\Controllers;

use App\Models\CarParking;
use App\Models\MotoParking;
use Illuminate\Http\Request;
use App\Models\Certificate;
class VerifyController extends Controller
{
  public function __construct()
    {
      $this->middleware('auth', ['except' => ['parkings','carpoints','motopoints']]);
    }
    public function search($code)
    {
      $data=\DB::table("certificates")/*->join("references","certificates.reference_id","=","references.id")*/->where('certificates.code', 'like', $code.'%')->first();//->select("certificates.tipo","certificates.solicitante","certificates.rubro","certificates.direccion","certificates.dim_ancho","certificates.dim_largo","certificates.dim_via_peatonal","references.types")->first();
      //return $data->code;
      //return $data;
      if(isset($data->id))
        return view('public.generate',compact("data"));
      else
        return view('notfound');
    }

    public function show($id){
      $data=\DB::table("certificates")->where('certificates.code',$id)->first();
      if(isset($data->id))
        return view('public.show', [
          'data' => $data
        ]);
      else
        return view('notfound');
    }

    public function parkings(){
      return view('public.parkings');
    }

    public function carpoints(){
      return CarParking::all();
    }

    public function motopoints(){
      return MotoParking::all();
    }
}
