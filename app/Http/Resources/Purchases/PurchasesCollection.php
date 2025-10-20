<?php

namespace App\Http\Resources\Purchases;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchasesCollection extends ResourceCollection {

    public function toArray($request) {
        return $this->collection->map(function ($formula) use ($request) {
            return (new PurchaseResource($formula))->toArray($request);
        });
    }

}