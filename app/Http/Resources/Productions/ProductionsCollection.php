<?php

namespace App\Http\Resources\Productions;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductionsCollection extends ResourceCollection{

    public function toArray($request) {
        return $this->collection->map(function ($production) {
            return [
                'id'                => $production->id,
                'client'            => $production->client ? $production->client->name : "",
                'consecutive'       => $production->consecutive,
                'date'              => $production->date,
                'user'              => $production->user ? $production->user->name : "",
                'product'           => $production->product,
                'tons_produced'     => $production->tons_produced,
                'shift'             => $production->shift ? $production->shift->name : "",
                'machine'           => $production->machine ? $production->machine->name : "",
                'formula'           => $production->formula ? $production->formula->name : "",
                'observations'      => $production->observations,
                'type'              => $production->type,
                'created_at'        => $production->created_at,
                'updated_at'        => $production->updated_at,
            ];
        });
    }

}
