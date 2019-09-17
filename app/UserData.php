<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    protected $fillable = [ 'session', 'data' ];
    protected $casts = [ 
        'data' => 'array'
    ];
}
