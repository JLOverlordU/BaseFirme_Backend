<?php

namespace App\Http\Resources\Administrable\Users\Roles;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource{

    public function toArray($request){
        return [
            'id'            => $this->id,
            'slug'          => $this->slug,
            'name'          => $this->name,
            'description'   => $this->description,
            'permissions'   => $this->array,
        ];
    }

}
