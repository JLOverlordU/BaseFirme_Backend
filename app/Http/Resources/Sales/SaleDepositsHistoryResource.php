<?php

namespace App\Http\Resources\Sales;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class SaleDepositsHistoryResource extends JsonResource {

    public function toArray($request) {
        return [
            'id'        => $this->id,
            'sale'      => $this->sale,
            'user'      => $this->user ? $this->user->name : "",
            'client'    => $this->client ? $this->client->name : "",
            'date'      => Carbon::parse($this->date)->format('d/m/Y') ?? null,
            'amount'    => $this->amount,
        ];
    }

}