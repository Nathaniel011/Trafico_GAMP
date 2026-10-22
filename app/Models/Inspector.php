<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Inspector extends Authenticatable
{
    use HasApiTokens;

    protected $hidden = ['password', 'remember_token'];
}
