<?php

namespace App\Http\Resources\Products;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductsStockHistoryCollection extends ResourceCollection{

    public function toArray($request) {
        return $this->collection->map(function ($history) use ($request) {
            return (new ProductStockHistoryResource($history))->toArray($request);
        });
    }

}
