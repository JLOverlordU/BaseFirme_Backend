<?php

namespace App\Models\Maestras;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model{

    public $table = "machines";

    protected $fillable = [
        'id',
        'name'
    ];
    
}