<?php

namespace App\Http\Resources\Sales;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesCollection extends ResourceCollection {

    public function toArray($request) {
        return $this->collection->map(function ($sale) use ($request) {
            return (new SaleResource($sale))->toArray($request);
        });
    }

}