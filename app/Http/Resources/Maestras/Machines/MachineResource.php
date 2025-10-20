<?php

namespace App\Http\Resources\Maestras\Machines;

use Illuminate\Http\Resources\Json\JsonResource;

class MachineResource extends JsonResource{

    public function toArray($request){
        return [
            'id'    => $this->id,
            'name'  => $this->name,
        ];
    }

}
