<?php

namespace App\Http\Resources\Administrable\Users\Users;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource{

    public function toArray($request){
        return [
            'id'        => $this->id,
            'username'  => $this->username,
            'name'      => $this->name,
            'email'     => $this->email,
            'phone'     => $this->phone,
            'password'  => $this->password_decrypted,
            'role_id'   => $this->role_id,
            'role'      => $this->role ? $this->role->name : null,
        ];
    }

}
