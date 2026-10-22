<?php

namespace App\Http\Controllers;

use App\Http\Resources\PathPointResource;
use App\Models\Route;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function apiPath($routeId){
        $group = Route::find($routeId);
        $points = $group->pathPoints()->orderBy('order', 'asc');
        return PathPointResource::collection($points->get());
    }
}
