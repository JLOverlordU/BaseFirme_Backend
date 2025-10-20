<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

use App\Models\Administrable\User;
use App\Models\Administrable\ClientProvider;

class Sale extends Model{

    protected $table = 'sales';

    protected $fillable = [
        'user_id', 
        'consecutive', 
        'date', 
        'client_id', 
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

    public function client(){
        return $this->belongsTo(ClientProvider::class, 'client_id')->where("type", "client");
    }

    public function details(){
        return $this->hasMany(SaleDetails::class, 'sale_id')->where("status", "activo")->with("product");
    }

}