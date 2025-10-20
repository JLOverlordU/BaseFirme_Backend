<?php

namespace App\Http\Resources\Sales;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Carbon\Carbon;

class SalesPurchasesDetailsCollection extends ResourceCollection{

    public function toArray($request) {
        return $this->collection->map(function ($item) {
            return [
                'id'                        => $item->id,
                'typeItemCast'              => $item->typeItem == "sale" ? "Venta" : "Compra",
                'consecutive'               => $item->typeItem == "sale" ? ($item->sale->consecutive ?? "") : ($item->purchase->consecutive ?? ""),
                'date'                      => $item->typeItem == "sale" ? ($item->sale->date ?? "") : ($item->purchase->date ?? ""),
                'sale_purchase'             => $item->typeItem == "sale" ? $item->sale : $item->purchase,
                'client_provider'           => $item->typeItem == "sale" ? ($item->sale->client->name ?? "") : ($item->purchase->provider->name ?? ""),
                'user'                      => $item->typeItem == "sale" ? ($item->sale->user->name ?? "") : ($item->purchase->user->name ?? ""),
                'product'                   => $item->product,
                'amount'                    => $item->amount,
                'amount_kg'                 => $item->amount_kg,
                'amount_saco'               => $item->amount_saco,
                'name_unit_measure'         => $item->name_unit_measure ?? "",
                'equivalent'                => $item->equivalent ?? "",
                'price'                     => $item->price,
                'total'                     => $item->total,
            ];
        });
    }

}
