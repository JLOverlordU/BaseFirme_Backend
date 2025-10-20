<?php

namespace App\Http\Resources\Sales;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesDetailsCollection extends ResourceCollection {

    public function toArray($request) {
        return $this->collection->map(function ($details) use ($request) {
            return (new SaleDetailsResource($details))->toArray($request);
        });
    }

}