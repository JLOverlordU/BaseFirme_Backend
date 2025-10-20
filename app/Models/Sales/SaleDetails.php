<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

use App\Models\Products\Product;
use App\Models\Maestras\UnitMeasure;

class SaleDetails extends Model{

    protected $table = 'sale_details';

    protected $fillable = [
        'sale_id',
        'product_id',
        'amount',
        'amount_kg',
        'amount_saco',
        'um',
        'name_unit_measure',
        'equivalent',
        'price',
        'total',
    ];

    public function sale(){
        return $this->belongsTo(Sale::class, 'sale_id')->with('user', 'client');
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

}