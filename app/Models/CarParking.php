<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CarParking extends Model
{
    protected $table = 'carparking';
    protected $casts = ['accuracy' => 'float', 'located_at' => \App\Casts\UtcTimestamp::class];
}
