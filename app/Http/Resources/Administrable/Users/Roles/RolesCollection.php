<?php

namespace App\Http\Resources\Administrable\Users\Roles;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RolesCollection extends ResourceCollection{

    public function toArray($request){
        return parent::toArray($request);
    }

}
