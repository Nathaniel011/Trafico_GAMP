<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'groups';

    public function transportLines(){
        return $this->hasMany('App\Models\TransportLine', 'group_id', 'id');
    }

    public function getCreationYearAttribute(){
        $date = $this->creation_date;
        if($date!=null){
            $date = new Carbon($date);
            return $date->format('Y');
        }
        return '';
    }

    public function getImageAttribute(){
        $path = $this->logo_path;
        if(strlen((string) $path)>0){
            return '/storage/'.$path;
        }
        return 'img/defaults/default512x512.png';
    }
}
