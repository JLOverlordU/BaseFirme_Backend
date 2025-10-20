<?php

namespace App\Http\Resources\Products;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductsCollection extends ResourceCollection{

    public function toArray($request) {
        return $this->collection->map(function ($product) {
            return [
                'id'                => $product->id,
                'cod_product'       => $product->cod_product ?? "",
                'name'              => $product->name,
                'id_process'        => $product->id_process,
                'process'           => $product->process ? $product->process->name : "",
                // 'presentation'      => $product->presentation ? $product->presentation->name : null,
                'id_unit_measure'   => $product->id_unit_measure,
                'unit_measure'      => $product->unitMeasure ? $product->unitMeasure->name : "",
                'unit_measure_data' => $product->unitMeasure ? $product->unitMeasure : null,
                'price'             => $product->price,
                'price_purchase'    => $product->price_purchase,
                'stock'             => $product->stock,
                'converted_stock'   => $product->converted_stock,
                'minimum_quantity'  => $product->minimum_quantity,
                'equivalent'        => $product->equivalent,
                'converted_price'   => $product->converted_price,
                'converted_price_purchase'   => $product->converted_price_purchase,
                'type'              => $product->type
            ];
        });
    }

}
