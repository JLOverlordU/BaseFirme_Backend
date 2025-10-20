<?php

namespace App\Http\Resources\Productions;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductionResource extends JsonResource{

    public function toArray($request){
        return [
            'id'                => $this->id,
            'client_id'         => $this->client_id,
            'client'            => $this->client ? $this->client->name : null,
            'clientData'        => $this->client ? $this->client : null,
            'consecutive'       => $this->consecutive,
            'date'              => $this->date,
            'user_id'           => $this->user_id,
            'user'              => $this->user ? $this->user->name : null,
            'product_id'        => $this->product_id,
            'product'           => $this->product ? $this->product->name : null,
            'productData'       => $this->product,
            'tons_produced'     => $this->tons_produced,
            'shift_id'          => $this->shift_id,
            'shift'             => $this->shift ? $this->shift->name : null,
            'machine_id'        => $this->machine_id,
            'machine'           => $this->machine ? $this->machine->name : null,
            'formula_id'        => $this->formula_id,
            'formula'           => $this->formula ? $this->formula->name : null,
            'formulaData'       => $this->formula ? $this->formula : null,
            'observations'      => $this->observations,
            'packing'           => $this->packing,
            'amount'            => $this->amount,
            'type'              => $this->type,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }

}
