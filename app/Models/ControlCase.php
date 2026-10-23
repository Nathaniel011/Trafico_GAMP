<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ControlCase extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['caseta_id' => 'integer', 'closed_at' => 'datetime'];
    public function notices() { return $this->hasMany(\App\Notice::class); }
}
