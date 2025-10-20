<?php

namespace App\Http\Resources\Products;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ProductStockHistoryResource extends JsonResource{

    public function toArray($request){

        $typeMapping = [
            'venta_aumento'             => ['type' => 'VENTA',    'description' => 'Aumentó'],
            'venta_disminuye'           => ['type' => 'VENTA',    'description' => 'Disminuyó'],
            'compra_aumento'            => ['type' => 'COMPRA',   'description' => 'Aumentó'],
            'compra_disminuye'          => ['type' => 'COMPRA',   'description' => 'Disminuyó'],
            'produccion_aumento'        => ['type' => 'PRODUCCION','description' => 'Aumentó'],
            'produccion_disminuye'      => ['type' => 'PRODUCCION','description' => 'Disminuyó'],
            'transferencia_aumento'     => ['type' => 'TRANSFERENCIA', 'description' => 'Aumentó'],
            'transferencia_disminuye'   => ['type' => 'TRANSFERENCIA', 'description' => 'Disminuyó'],
            'stock'                     => ['type' => 'STOCK',    'description' => 'Aumentó'],
        ];

        $mappedType = $typeMapping[$this->type] ?? ['type' => 'DESCONOCIDO', 'description' => 'Desconocido'];

        return [
            'id'                => $this->id,
            'product_id'        => $this->product_id,
            'productData'       => $this->product,
            'product'           => $this->product->name,
            'stock'             => $this->stock,
            'converted_stock'   => $this->converted_stock,
            'slug'              => $this->product->unitMeasure ? $this->product->unitMeasure->slug : "saco",
            'date'              => Carbon::parse($this->date)->format('d/m/Y') ?? null,
            'type'              => $mappedType['type'],
            'description'       => $mappedType['description'],
        ];
    }

}
