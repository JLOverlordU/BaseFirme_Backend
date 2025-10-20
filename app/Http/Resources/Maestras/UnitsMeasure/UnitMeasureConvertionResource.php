<?php

namespace App\Http\Resources\Maestras\UnitsMeasure;

use Illuminate\Http\Resources\Json\JsonResource;

class UnitMeasureConvertionResource extends JsonResource{

    public function toArray($request){
        return [
            'id'                    => $this->id,
            'unitMeasure'           => $this->unitMeasure,
            'unitMeasureConvert'    => $this->unitMeasureConvert,
            'amount'                => $this->amount,
            'text1'                 => $this->unitMeasure->name ?? "",
            'text2'                 => $this->unitMeasureConvert->name ?? "",
            // 'text2'                 => $this->amount . " " .($this->unitMeasureConvert->name ?? ""),
        ];
    }

}
