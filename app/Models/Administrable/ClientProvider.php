<?php

namespace App\Models\Administrable;

use Illuminate\Database\Eloquent\Model;

use App\Models\Sales\Sale;
use App\Models\Sales\SaleDepositsHistory;
use App\Models\Purchases\PurchaseDepositsHistory;

class ClientProvider extends Model{

    public $table = "clients_providers";

    protected $fillable = [
        'id',
        'document',
        'type',
        'name',
        'phone',
        'email',
        'address',
        'description',
        'status',
    ];

    public function lastDeposit(){
        return $this->hasOne(SaleDepositsHistory::class, 'client_id', 'id')
            ->orderBy('created_at', 'desc')
            ->withDefault([
                'amount' => 0,
            ]);
    }

    public function lastDepositProvider(){
        return $this->hasOne(PurchaseDepositsHistory::class, 'provider_id', 'id')
            ->orderBy('created_at', 'desc')
            ->withDefault([
                'amount' => 0,
            ]);
    }

    public function depositsClients(){
        return $this->hasMany(SaleDepositsHistory::class, 'client_id');
    }

    public function depositsProviders(){
        return $this->hasMany(PurchaseDepositsHistory::class, 'provider_id');
    }

    public function typeSaleContado(){
        return $this->hasMany(Sale::class, 'client_id')->where('type', 'contado');
    }

    public function typeSaleCredito(){
        return $this->hasMany(Sale::class, 'client_id')->where('type', 'credito');
    }

    public function typePurchaseContado(){
        return $this->hasMany(Purchase::class, 'provider_id')->where('type', 'contado');
    }

    public function typePurchaseCredito(){
        return $this->hasMany(Purchase::class, 'provider_id')->where('type', 'credito');
    }

}