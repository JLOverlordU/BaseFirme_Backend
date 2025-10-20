<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

use App\Models\Administrable\User;
use App\Models\Administrable\ClientProvider;

class SaleDepositsHistory extends Model{

    protected $table = 'sale_deposits_history';

    protected $fillable = [
        'sale_id', 
        'user_id', 
        'client_id', 
        'date', 
        'amount', 
        'status'
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client(){
        return $this->belongsTo(ClientProvider::class, 'client_id');
    }

    public function sale(){
        return $this->belongsTo(Sale::class, 'sale_id');
    }

}