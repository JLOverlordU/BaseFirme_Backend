<?php

namespace App\Models\Productions;

use Illuminate\Database\Eloquent\Model;

use App\Models\Administrable\User;
use App\Models\Administrable\ClientProvider;
use App\Models\Products\Product;
use App\Models\Maestras\Shift;
use App\Models\Maestras\Machine;
use App\Models\Formulas\Formula;

class Production extends Model{

    protected $table = 'productions';

    protected $fillable = [
        'id',
        'client_id',
        'consecutive',
        'date',
        'user_id',
        'product_id',
        'tons_produced',
        'shift_id',
        'machine_id',
        'formula_id',
        'packing',
        'amount',
        'observations',
        'type',
    ];

    public function client(){
        return $this->belongsTo(ClientProvider::class, 'client_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id')->with('process', 'unitMeasure')->where('status', 'activo');
    }

    public function shift(){
        return $this->belongsTo(Shift::class, 'shift_id')->where('status', 'activo');
    }

    public function machine(){
        return $this->belongsTo(Machine::class, 'machine_id')->where('status', 'activo');
    }

    public function formula(){
        return $this->belongsTo(Formula::class, 'formula_id')->with('details')->where('status', 'activo');
    }

    public function details(){
        return $this->hasMany(ProductionDetails::class, 'id')->where("status", "activo")->with("product");
    }

}