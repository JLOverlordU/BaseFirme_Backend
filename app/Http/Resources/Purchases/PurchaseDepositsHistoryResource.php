<?php

namespace App\Http\Resources\Purchases;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PurchaseDepositsHistoryResource extends JsonResource {

    public function toArray($request) {
        return [
            'id'        => $this->id,
            'purchase'  => $this->purchase,
            'user'      => $this->user ? $this->user->name : "",
            'provider'  => $this->provider ? $this->provider->name : "",
            'date'      => Carbon::parse($this->date)->format('d/m/Y') ?? null,
            'amount'    => $this->amount,
        ];
    }

}