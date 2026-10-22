<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    protected $table = 'references';

    public function getFormattedStartAttribute(){
        $date = Carbon::createFromFormat('H:i:s', $this->start_hour);
        return $date->format('H:i');
    }

    public function getFormattedEndAttribute(){
        $date = Carbon::createFromFormat('H:i:s', $this->end_hour);
        return $date->format('H:i');
    }
}
