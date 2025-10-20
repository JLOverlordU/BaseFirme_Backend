<?php

namespace App\Exports;

use App\Models\Sales\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;

class SalesExport implements FromCollection{

    // public function collection(){
    //     return Sale::where('status', '!=', 'eliminado')->get(['cod_product', 'name', 'unit_measure', 'price', 'stock']);
    // }

    // public function headings(): array{
    //     return [
    //         'Código',
    //         'Nombre',
    //         'Unidad de Medida',
    //         'Precio',
    //         'Stock',
    //     ];
    // }

    use Exportable;
    
    public function collection(){
        return Sale::all();
    }

}
