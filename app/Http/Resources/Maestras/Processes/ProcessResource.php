<?php

namespace App\Http\Resources\Maestras\Processes;

use Illuminate\Http\Resources\Json\JsonResource;

class ProcessResource extends JsonResource{

    public function toArray($request){
        return [
            'id'    => $this->id,
            'name'  => $this->name,
        ];
    }

}
