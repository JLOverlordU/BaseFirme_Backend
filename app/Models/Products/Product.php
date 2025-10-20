<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

use App\Models\Maestras\Process;
// use App\Models\Maestras\Presentation;
use App\Models\Maestras\UnitMeasure;
use App\Models\Maestras\UnitMeasureConvertion;
use App\Models\Formulas\Formula;

class Product extends Model{

    public $table = "products";

    protected $fillable = [
        'cod_product',
        'name',
        'id_process',
        'id_presentation',
        'id_unit_measure',
        'id_formula',
        'price',
        'price_purchase',
        'stock',
        'converted_stock',
        'minimum_quantity',
        'equivalent',
        'converted_price',
        'converted_price_purchase',
        'type',
    ];

    public function process(){
        return $this->belongsTo(Process::class, 'id_process')->where('status', 'activo');
    }

    // public function presentation(){
    //     return $this->belongsTo(Presentation::class, 'id_presentation')->where('status', 'activo');
    // }

    public function formula(){
        return $this->belongsTo(Formula::class, 'id_formula')->with('details')->where('status', 'activo');
    }

    public function unitMeasure(){
        return $this->belongsTo(UnitMeasure::class, 'id_unit_measure')->where('status', 'activo');
    }

    public function convertions(){
        return $this->belongsTo(UnitMeasureConvertion::class, 'id_unit_measure')->where('status', 'activo');
    }

}