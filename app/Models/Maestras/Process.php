<?php

namespace App\Models\Maestras;

use Illuminate\Database\Eloquent\Model;

class Process extends Model{

    public $table = "process";

    protected $fillable = [
        'id',
        'name'
    ];
    
}