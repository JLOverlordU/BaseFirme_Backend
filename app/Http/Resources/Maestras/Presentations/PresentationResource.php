<?php

namespace App\Http\Resources\Maestras\Presentations;

use Illuminate\Http\Resources\Json\JsonResource;

class PresentationResource extends JsonResource{

    public function toArray($request){
        return [
            'id'    => $this->id,
            'name'  => $this->name,
        ];
    }

}
