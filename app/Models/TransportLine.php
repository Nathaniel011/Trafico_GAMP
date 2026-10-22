<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportLine extends Model
{
    protected $table = 'transport_lines';

    public function routes(){
        return $this->hasMany('App\Models\Route', 'transport_line_id', 'id');
    }
}
