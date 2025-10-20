<?php

namespace App\Models\Maestras;

use Illuminate\Database\Eloquent\Model;

class UnitMeasure extends Model{

    public $table = "units_measure";

    protected $fillable = [
        'id',
        'slug',
        'name'
    ];

}