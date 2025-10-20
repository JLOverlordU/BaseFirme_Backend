<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

use App\Models\Administrable\User;
use App\Models\Administrable\ClientProvider;

class Purchase extends Model{

    protected $table = 'purchases';

    protected $fillable = [
        'user_id', 
        'consecutive', 
        'date', 
        'provider_id', 
        'description',
        'subtotal',
        'deposit',
        'consumption',
        'total',
        'type',
        'boleta_factura',
        'ruc',
        'status',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(){
        return $this->belongsTo(ClientProvider::class, 'provider_id')->where("type", "provider");
    }
    
    public function details(){
        return $this->hasMany(PurchaseDetails::class, 'purchase_id')->where("status", "activo")->with("product");
    }

}