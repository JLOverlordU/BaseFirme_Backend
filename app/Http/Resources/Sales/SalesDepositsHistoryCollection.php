<?php

namespace App\Http\Resources\Sales;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesDepositsHistoryCollection extends ResourceCollection {

    public function toArray($request) {
        return $this->collection->map(function ($history) use ($request) {
            return (new SaleDepositsHistoryResource($history))->toArray($request);
        });
    }

}