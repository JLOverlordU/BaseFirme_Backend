<?php

namespace App\Models\Formulas;

use Illuminate\Database\Eloquent\Model;

use App\Models\Products\Product;

class FormulaDetails extends Model{

    protected $table = 'formula_details';

    protected $fillable = [
        'formula_id',
        'product_id',
        'price',
        'amount',
        'cost',
        'type',
        'status',
    ];

    public function formula(){
        return $this->belongsTo(Formula::class, 'formula_id');
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

}