<?php

namespace App\Http\Resources\Administrable\Users\Roles;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PermissionCollection extends ResourceCollection{

    public function toArray($request){
        return parent::toArray($request);
    }

}
