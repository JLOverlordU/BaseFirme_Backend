<?php

namespace App\Http\Resources\Formulas;

use Illuminate\Http\Resources\Json\ResourceCollection;

class FormulasCollection extends ResourceCollection{

    public function toArray($request) {
        return $this->collection->map(function ($formula) {
            return [
                'id'                => $formula->id,
                'product'           => $formula->product ?? null,
                'name'              => $formula->name,
                'unit_measure_id'   => $formula->unit_measure_id,
                'unit_measure'      => $formula->unitMeasure ? $formula->unitMeasure->name : "",
                'details'           => $formula->details ?? [],
                'details_nucleos'   => $formula->detailsNucleos ?? [],
                'total_macros'      => $formula->total_macros,
                'total_nucleo'      => $formula->total_nucleo,
                'total'             => $formula->total,
                'cost_macros'       => $formula->cost_macros,
                'cost_nucleo'       => $formula->cost_nucleo,
                'cost_total'        => $formula->cost_total,
                'created_at'        => $formula->created_at,
                'updated_at'        => $formula->updated_at,
            ];
        });
    }

}
