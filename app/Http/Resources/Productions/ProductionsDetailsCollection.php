<?php

namespace App\Http\Resources\Productions;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductionsDetailsCollection extends ResourceCollection{

    public function toArray($request) {
        return $this->collection->map(function ($details) use ($request) {
            return (new ProductionDetailsResource($details))->toArray($request);
        });
    }

}
