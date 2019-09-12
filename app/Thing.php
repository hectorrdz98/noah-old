<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Thing extends Model
{
    protected $fillable = ['thingName', 'thingTypes', 'thingRels'];
    protected $casts = [ 
        'thingTypes' => 'array', 
        'thingRels' => 'array' 
    ];
}
