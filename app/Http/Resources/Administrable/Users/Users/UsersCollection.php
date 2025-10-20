<?php

namespace App\Http\Resources\Administrable\Users\Users;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UsersCollection extends ResourceCollection{

    public function toArray($request) {
        return $this->collection->map(function ($user) {
            return [
                'id'        => $user->id,
                'username'  => $user->username,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'role'      => $user->role ? $user->role->name : null,
            ];
        });
    }

}
