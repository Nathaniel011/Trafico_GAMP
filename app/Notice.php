<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class Notice extends Model
{
    public function getFormattedDateAttribute(){
        if($this->notice_date){
            return (new Carbon($this->notice_date))->format('d/m/Y H:i');
        }
        return '...';
    }
}
