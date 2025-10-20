<?php

namespace App\Http\Resources\Sales;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class SaleDetailsResource extends JsonResource {

    public function toArray($request) {
        return [
            'id'                        => $this->id,
            'sale'                      => $this->sale,
            'product'                   => $this->product,
            'amount'                    => $this->amount,
            'amount_kg'                 => $this->amount_kg,
            'amount_saco'               => $this->amount_saco,
            'um'                        => $this->um ?? "",
            'name_unit_measure'         => $this->name_unit_measure ?? "",
            'equivalent'                => $this->equivalent ?? "",
            'price'                     => $this->price,
            'total'                     => $this->total,
        ];
    }

}