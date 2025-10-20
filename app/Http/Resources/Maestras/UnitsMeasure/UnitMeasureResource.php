<?php

namespace App\Http\Resources\Maestras\UnitsMeasure;

use Illuminate\Http\Resources\Json\JsonResource;

class UnitMeasureResource extends JsonResource{

    public function toArray($request){
        return [
            'id'    => $this->id,
            'name'  => $this->name,
        ];
    }

}
