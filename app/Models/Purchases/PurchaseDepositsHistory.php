<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

use App\Models\Administrable\User;
use App\Models\Administrable\ClientProvider;

class PurchaseDepositsHistory extends Model{

    protected $table = 'purchase_deposits_history';

    protected $fillable = [
        'purchase_id', 
        'user_id',
        'provider_id',
        'date', 
        'amount', 
        'status'
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(){
        return $this->belongsTo(ClientProvider::class, 'provider_id');
    }

    public function purchase(){
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

}