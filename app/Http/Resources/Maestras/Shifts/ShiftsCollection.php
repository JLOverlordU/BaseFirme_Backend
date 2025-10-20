<?php

namespace App\Http\Resources\Maestras\Shifts;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ShiftsCollection extends ResourceCollection{

    public function toArray($request){
        return parent::toArray($request);
    }

}
