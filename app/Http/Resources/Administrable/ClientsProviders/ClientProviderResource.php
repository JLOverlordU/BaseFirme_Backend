<?php

namespace App\Http\Resources\Administrable\ClientsProviders;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientProviderResource extends JsonResource{

    public function toArray($request){
        return [
            'id'            => $this->id,
            'document'      => $this->document,
            'type'          => $this->type,
            'name'          => $this->name,
            'phone'         => $this->phone ?? "",
            'email'         => $this->email,
            'address'       => $this->address,
            'description'   => $this->description
        ];
    }

}
