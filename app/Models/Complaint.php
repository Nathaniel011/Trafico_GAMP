<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $table = 'complaints';

    public function getImageAttribute(){
        $path = $this->photo_path;
        if($path != null && strlen($path)>0){
            return asset('/storage/'.$path);
        }
        return '';
    }

    public function getFormattedCreatedAtAttribute(){
        return (new Carbon($this->created_at))->format('d/m/Y H:i');
    }
}
