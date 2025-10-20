<?php

namespace App\Http\Resources\Formulas;

use Illuminate\Http\Resources\Json\JsonResource;

class FormulaResource extends JsonResource{

    public function toArray($request){
        return [
            'id'                => $this->id,
            'product'           => $this->product ?? null,
            'name'              => $this->name,
            'unit_measure_id'   => $this->unit_measure_id,
            'unit_measure'      => $this->unitMeasure? $this->unitMeasure->name : null,
            'details'           => $this->details ?? [],
            'details_nucleos'   => $this->detailsNucleos ?? [],
            'total_macros'      => $this->total_macros,
            'total_nucleo'      => $this->total_nucleo,
            'total'             => $this->total,
            'cost_macros'       => $this->cost_macros,
            'cost_nucleo'       => $this->cost_nucleo,
            'cost_total'        => $this->cost_total,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }

}
