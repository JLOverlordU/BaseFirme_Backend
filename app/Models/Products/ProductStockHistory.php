<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

class ProductStockHistory extends Model{

    public $table = "product_stocks_history";

    protected $fillable = [
        'product_id',
        'stock',
        'converted_stock',
        'date',
        'type',
        'slug_um',
    ];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id')->with("unitMeasure");
    }

}