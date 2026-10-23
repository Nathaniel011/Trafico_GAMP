<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TemporaryPhoto extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['user_id' => 'integer', 'expires_at' => 'datetime', 'consumed_at' => 'datetime'];
}
