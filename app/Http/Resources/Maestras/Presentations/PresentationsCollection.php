<?php

namespace App\Http\Resources\Maestras\Presentations;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PresentationsCollection extends ResourceCollection{

    public function toArray($request){
        return parent::toArray($request);
    }

}
