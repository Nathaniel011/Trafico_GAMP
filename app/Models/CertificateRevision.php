<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateRevision extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['snapshot' => 'array', 'created_at' => 'datetime'];
}
