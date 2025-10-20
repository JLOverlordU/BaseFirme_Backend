<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

use App\Models\Products\Product;

class PurchaseDetails extends Model{

    protected $table = 'purchase_details';

    protected $fillable = [
        'purchase_id',
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

    public function purchase(){
        return $this->belongsTo(Purchase::class, 'purchase_id')->with('user', 'provider');;
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

}