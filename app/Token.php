<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $fillable = [ 'value', 'tag', 'synonyms' ];
    protected $casts = [ 
        'synonyms' => 'array'
    ];
}
