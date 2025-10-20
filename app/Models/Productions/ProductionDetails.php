<?php

namespace App\Models\Productions;

use Illuminate\Database\Eloquent\Model;

use App\Models\Maestras\Process;
use App\Models\Maestras\UnitMeasure;
use App\Models\Products\Product;

class ProductionDetails extends Model{

    public $table = "productions_details";

    protected $fillable = [
        'id_production',
        'id_product',
        'cod_product',
        'name',
        'id_process',
        'process',
        'presentation',
        'unit_measure',
        'price',
        'amount',
        'type',
        'status'
    ];

    public function production(){
        return $this->belongsTo(Production::class, 'id_production')->with('shift');
    }

    public function product(){
        return $this->belongsTo(Product::class, 'id_product');
    }

    public function process(){
        return $this->belongsTo(Process::class, 'id_process')->where('status', 'activo');
    }

}