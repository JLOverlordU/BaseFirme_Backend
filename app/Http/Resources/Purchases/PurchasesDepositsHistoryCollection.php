<?php

namespace App\Http\Resources\Purchases;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchasesDepositsHistoryCollection extends ResourceCollection {

    public function toArray($request) {
        return $this->collection->map(function ($history) use ($request) {
            return (new PurchaseDepositsHistoryResource($history))->toArray($request);
        });
    }

}