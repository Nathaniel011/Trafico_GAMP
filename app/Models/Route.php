<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'routes';

    public function pathPoints(){
        return $this->hasMany('App\Models\PathPoint', 'route_id', 'id');
    }

    public function group(){
        return $this->belongsTo('App\Models\Group');
    }
}
