<?php

namespace App\Http\Resources\Purchases;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource {

    public function toArray($request) {
        return [
            'id'            => $this->id,
            'consecutive'   => $this->consecutive,
            'date'          => $this->date ?? null,
            'description'   => $this->description ?? "",
            'subtotal'      => $this->subtotal,
            'deposit'       => $this->deposit,
            'consumption'   => $this->consumption,
            'total'         => $this->total,
            'type'          => $this->type,
            'boleta_factura'=> $this->boleta_factura == "boleta" ? "Boleta" : "Factura",
            'ruc'           => $this->ruc,
            'user'          => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null,
            'provider'      => $this->provider ? ['id' => $this->provider->id, 'name' => $this->provider->name] : null,
            'details'       => $this->details ?? [],
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }

}