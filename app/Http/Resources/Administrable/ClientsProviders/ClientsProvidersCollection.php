<?php

namespace App\Http\Resources\Administrable\ClientsProviders;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ClientsProvidersCollection extends ResourceCollection{

    public function toArray($request){
        return $this->collection->map(function ($user) {
            return [
                'id'                    => $user->id,
                'document'              => $user->document,
                'name'                  => $user->name,
                'phone'                 => $user->phone ?? "",
                'email'                 => $user->email,
                'address'               => $user->address,
                'lastDeposit'           => $user->lastDeposit->amount ?? 0,
                'lastDepositProvider'   => $user->lastDepositProvider->amount ?? 0,
                'description'           => $user->description ?? "",
            ];
        });
    }

}
