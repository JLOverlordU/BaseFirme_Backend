<?php

namespace App\Models\Maestras;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model{

    public $table = "shifts";

    protected $fillable = [
        'id',
        'name'
    ];
    
}