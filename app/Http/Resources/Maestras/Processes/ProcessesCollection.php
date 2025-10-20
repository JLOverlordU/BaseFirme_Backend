<?php

namespace App\Http\Resources\Maestras\Processes;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProcessesCollection extends ResourceCollection{

    public function toArray($request){
        return parent::toArray($request);
    }

}
