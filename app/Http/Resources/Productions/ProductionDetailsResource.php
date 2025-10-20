<?php

namespace App\Http\Resources\Productions;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductionDetailsResource extends JsonResource{

    public function toArray($request){
        return [
            'id'                => $this->id,
            'production'        => $this->production,
            'consecutive'       => "#".$this->production->consecutive,
            'tons_produced'     => $this->production->tons_produced ?? "",
            'shift'             => $this->production->shift->name ?? "",
            'machine'           => $this->production->machine->name ?? "",
            'formula'           => $this->production->formula->name ?? "",
            'observations'      => $this->production->observations ?? "",
            'product'           => $this->product ?? null,
            'cod_product'       => $this->cod_product,
            'name'              => $this->name,
            'process'           => $this->process,
            'presentation'      => $this->presentation,
            'unit_measure'      => $this->unit_measure,
            'price'             => $this->price,
            'amount'            => $this->amount,
            'type'              => $this->type,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }

}
