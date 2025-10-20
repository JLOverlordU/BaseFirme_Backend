<?php

namespace App\Models\Maestras;

use Illuminate\Database\Eloquent\Model;

class UnitMeasureConvertion extends Model{

    public $table = "units_measure_conversions";

    protected $fillable = [
        'id',
        'id_unit_measure',
        'id_unit_measure_convert',
        'amount',
    ];

    public function unitMeasure(){
        return $this->belongsTo(UnitMeasure::class, 'id_unit_measure')->where('status', 'activo');
    }

    public function unitMeasureConvert(){
        return $this->belongsTo(UnitMeasure::class, 'id_unit_measure_convert')->where('status', 'activo');
    }

}