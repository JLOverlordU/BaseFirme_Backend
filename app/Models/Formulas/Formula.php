<?php

namespace App\Models\Formulas;

use Illuminate\Database\Eloquent\Model;

use App\Models\Maestras\UnitMeasure;
use App\Models\Products\Product;

class Formula extends Model{

    protected $table = 'formulas';

    protected $fillable = [
        'name', 
        'unit_measure_id', 
        'total_macros',
        'total_nucleo',
        'total',
        'cost_macros',
        'cost_nucleo',
        'cost_total',
    ];

    public function unitMeasure(){
        return $this->belongsTo(UnitMeasure::class, 'unit_measure_id');
    }

    public function details(){
        return $this->hasMany(FormulaDetails::class, 'formula_id')->where('type', '=', 'insumo')->where('status', '=', 'activo')->with("product", "product.process", "product.unitMeasure");
    }

    public function detailsNucleos(){
        return $this->hasMany(FormulaDetails::class, 'formula_id')->where('type', '=', 'nucleo')->where('status', '=', 'activo')->with("product", "product.process", "product.unitMeasure");
    }

    public function product() {
        return $this->hasOne(Product::class, 'id_formula', 'id');
    }

}