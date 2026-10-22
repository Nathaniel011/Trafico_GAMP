<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PathPoint extends Model
{
    protected $table = 'path_points';

    public function route(){
        return $this->belongsTo('App\Models\Route');
    }
}
