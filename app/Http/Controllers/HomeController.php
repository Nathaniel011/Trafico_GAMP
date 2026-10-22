<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        return view('home.index');
    }

    public function notFound()
    {
        return view('notfound');
    }

//    public function searchLocations(Request $request){
//        $query = $request->get('query', '*****');
//        $query = strtolower($query);
//        $query = "$query%2C+villa+imperial+de+potosi";
//
//
//    }

}
