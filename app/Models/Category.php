<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    public function children(){
        return $this->hasMany('App\Models\Category', 'category_id', 'id');
    }
}
