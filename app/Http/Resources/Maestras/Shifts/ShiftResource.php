<?php

namespace App\Http\Resources\Maestras\Shifts;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource{

    public function toArray($request){
        return [
            'id'    => $this->id,
            'name'  => $this->name,
        ];
    }

}
