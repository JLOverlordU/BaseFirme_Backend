<?php

namespace App\Http\Resources\Maestras\UnitsMeasure;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UnitsMeasureConvertionsCollection extends ResourceCollection {

    public function toArray($request) {
        return $this->collection->map(function ($unitMeasure) use ($request) {
            return (new UnitMeasureConvertionResource($unitMeasure))->toArray($request);
        });
    }

}