<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MotoParking extends Model
{
    protected $table = 'motoparking';
    protected $casts = ['accuracy' => 'float', 'located_at' => \App\Casts\UtcTimestamp::class];
}
