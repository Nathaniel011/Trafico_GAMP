<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public function getLevelClassAttribute(){
        $class = '';
        switch($this->level){
            case 'low':
                $class = 'info';
                break;
            case 'medium':
                $class = 'warning';
                break;
            case 'high':
                $class = 'danger';
                break;
        }
        return $class;
    }

    public function getLevelTextAttribute(){
        $text = '';
        switch($this->level){
            case 'low':
                $text = 'Aviso';
                break;
            case 'medium':
                $text = 'Tomar en cuenta';
                break;
            case 'high':
                $text = 'Importante';
                break;
        }
        return $text;
    }

    public function getFormattedCreatedAtAttribute(){
        $date = new Carbon($this->created_at);
        return $date->format('d/m/Y H:i');
    }
}
