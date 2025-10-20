<?php

namespace App\Models\Maestras;

use Illuminate\Database\Eloquent\Model;

class Presentation extends Model{

    public $table = "presentations";

    protected $fillable = [
        'id',
        'name'
    ];
    
}