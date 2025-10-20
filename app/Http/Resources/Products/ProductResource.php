<?php

namespace App\Http\Resources\Products;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource{

    public function toArray($request){
        return [
            'id'                => $this->id,
            'cod_product'       => $this->cod_product ?? "",
            'name'              => $this->name,
            'id_process'        => $this->id_process,
            'process'           => $this->process ? $this->process->name : null,
            // 'id_presentation'   => $this->id_presentation,
            // 'presentation'      => $this->presentation ? $this->presentation->name : null,
            'id_unit_measure'   => $this->id_unit_measure,
            'unit_measure'      => $this->unitMeasure ? $this->unitMeasure->name : null,
            'unit_measure_data' => $this->unitMeasure ? $this->unitMeasure : null,
            'price'             => $this->price,
            'price_purchase'    => $this->price_purchase,
            'stock'             => $this->stock,
            'converted_stock'   => $this->converted_stock,
            'minimum_quantity'  => $this->minimum_quantity,
            'equivalent'        => $this->equivalent,
            'converted_price'   => $this->converted_price,
            'converted_price_purchase'   => $this->converted_price_purchase,
            'type'              => $this->type
        ];
    }

}
