<?php

namespace App\Http\Resources\Sales;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class SaleResource extends JsonResource {

    public function toArray($request) {
        return [
            'id'            => $this->id,
            'consecutive'   => $this->consecutive,
            'date'          => Carbon::parse($this->date)->format('d/m/Y') ?? null,
            'description'   => $this->description ?? "",
            'subtotal'      => $this->subtotal,
            'deposit'       => $this->deposit,
            'consumption'   => $this->consumption,
            'total'         => $this->total,
            'type'          => $this->type,
            'boleta_factura'=> $this->boleta_factura == "boleta" ? "Boleta" : "Factura",
            'ruc'           => $this->ruc,
            'user'          => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null,
            'client'        => $this->client ? ['id' => $this->client->id, 'name' => $this->client->name] : null,
            'details'       => $this->details ?? [],
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }

}