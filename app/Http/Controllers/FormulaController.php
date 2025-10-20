<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;

use App\Models\Formulas\Formula;
use App\Models\Formulas\FormulaDetails;
use App\Models\Products\Product;
use App\Models\Maestras\UnitMeasure;
use App\Http\Resources\Formulas\FormulaResource;
use App\Http\Resources\Formulas\FormulasCollection;
use App\Http\Requests\Formulas\FormulaRequest;
use App\Http\Requests\Formulas\FormulaDetailRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

use Barryvdh\DomPDF\Facade\Pdf;

class FormulaController extends Controller{

    const NAME      = 'La fórmula';
    const GENDER    = 'a';
    const NAME2     = 'El detalle';
    const GENDER2   = 'o';

    //? Listar fórmulas
    public function index(Request $request){

        $filters = $request->all();

        $data = Formula::where('status', '!=', 'eliminado')
                        ->when(isset($filters['name']) && !empty($filters['name']), function ($q) use ($filters) {
                            return $q->where('name', 'like', '%' . $filters['name'] . '%');
                        })
                        ->when(isset($filters['unit_measure']) && !empty($filters['unit_measure']), function ($query) use ($filters) {
                            return $query->whereHas('unitMeasure', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['unit_measure'] . '%');
                            });
                        })
                        ->get();

        return new FormulasCollection($data);

    }

    //? Guardar fórmula
    public function store(FormulaRequest $request, FormulaDetailRequest $request2){

        try {

            DB::beginTransaction();

            $model = Formula::create([
                'name'              => $request->name,
                'unit_measure_id'   => $request->unit_measure_id,
                'total_macros'      => $request->total_macros,
                'total_nucleo'      => $request->total_nucleo,
                'total'             => $request->total,
                'cost_macros'       => $request->cost_macros,
                'cost_nucleo'       => $request->cost_nucleo,
                'cost_total'        => $request->cost_total,
            ]);

            //? Insumos
            if ($request2->has('details')) {
                foreach ($request2->details as $detail) {
                    FormulaDetails::create([
                        'formula_id' => $model['id'],
                        'product_id' => $detail['product_id'],
                        'amount'     => $detail['amount'],
                        'price'      => $detail['price'],
                        'cost'       => $detail['cost'],
                        'type'       => "insumo",
                    ]);
                }
            }

            //? Núcleos
            if ($request2->has('details_nucleos')) {
                foreach ($request2->details_nucleos as $detail) {
                    FormulaDetails::create([
                        'formula_id' => $model['id'],
                        'product_id' => $detail['product_id'],
                        'amount'     => $detail['amount'],
                        'price'      => $detail['price'],
                        'cost'       => $detail['cost'],
                        'type'       => "nucleo",
                    ]);
                }
            }

            //? Creamos el producto con marca Nutrivan

            $modelUnitMeasure = UnitMeasure::where('slug', "saco")->where('status', '!=', 'eliminado')->first();
            $idUnitMeasure = $modelUnitMeasure->id ?? null;

            $converted_price            = number_format($request->price / $request->equivalent, 4, '.', '');
            $converted_price_purchase   = number_format($request->price_purchase / $request->equivalent, 4, '.', '');

            Product::create([
                'cod_product'       => null,
                'name'              => $request->name,
                'id_process'        => null,
                'id_presentation'   => null,
                'id_unit_measure'   => $idUnitMeasure,
                'id_formula'        => $model['id'],
                'price'             => $request->price ?? 0,
                'converted_price'   => $converted_price ?? 0,
                'price_purchase'    => $request->price_purchase ?? 0,
                'converted_price_purchase'  => $converted_price_purchase ?? 0,
                'stock'             => $request->stock ?? 0,
                'equivalent'        => $request->equivalent ?? 0,
                'converted_stock'   => $request->converted_stock ?? 0,
                'type'              => "nutrivan",
                'minimum_quantity'  => 0,
            ]);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new FormulaResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Ver fórmula
    public function show($id){

        try {

            $model = Formula::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new FormulaResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar fórmula
    public function update(FormulaRequest $request, FormulaDetailRequest $request2, string $id){
        
        try {

            DB::beginTransaction();
            $model = Formula::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    // Product::where('id_formula', $model->id)->where('status', '!=', 'eliminado')->update(['price' => $request->cost_total]);

                    $model->update([
                        'name'              => $request->name,
                        'unit_measure_id'   => $request->unit_measure_id,
                        'total_macros'      => $request->total_macros,
                        'total_nucleo'      => $request->total_nucleo,
                        'total'             => $request->total,
                        'cost_macros'       => $request->cost_macros,
                        'cost_nucleo'       => $request->cost_nucleo,
                        'cost_total'        => $request->cost_total,
                    ]);

                    //? Insumos
                    if ($request2->has('details')) {
                        foreach ($request2->details as $detail) {
                            if($detail['id'] == "-1"){
                                FormulaDetails::create([
                                    'formula_id' => $model['id'],
                                    'product_id' => $detail['product_id'],
                                    'amount'     => $detail['amount'],
                                    'price'      => $detail['price'],
                                    'cost'       => $detail['cost'],
                                    'type'       => "insumo",
                                ]);
                            } else {
                                $modelFormula = FormulaDetails::where('id', $detail['id'])->first();
                                $modelFormula->update([
                                    'formula_id' => $model['id'],
                                    'product_id' => $detail['product_id'],
                                    'amount'     => $detail['amount'],
                                    'price'      => $detail['price'],
                                    'cost'       => $detail['cost'],
                                    'type'       => "insumo",
                                ]);
                            }
                        }
                    }

                    //? Núcleos
                    if ($request2->has('details_nucleos')) {
                        foreach ($request2->details_nucleos as $detail) {
                            if($detail['id'] == "-1"){
                                FormulaDetails::create([
                                    'formula_id' => $model['id'],
                                    'product_id' => $detail['product_id'],
                                    'amount'     => $detail['amount'],
                                    'price'      => $detail['price'],
                                    'cost'       => $detail['cost'],
                                    'type'       => "nucleo",
                                ]);
                            } else {
                                $modelFormula = FormulaDetails::where('id', $detail['id'])->first();
                                $modelFormula->update([
                                    'formula_id' => $model['id'],
                                    'product_id' => $detail['product_id'],
                                    'amount'     => $detail['amount'],
                                    'price'      => $detail['price'],
                                    'cost'       => $detail['cost'],
                                    'type'       => "nucleo",
                                ]);
                            }
                        }
                    }

                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new FormulaResource($model), 200);

                }

                DB::rollback();
                return SendResponse::message(false, 'update', self::NAME . ' ya se encuentra eliminad' . self::GENDER, null, 400);

            }

            DB::rollback();
            return SendResponse::message(false, 'update', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'update', self::NAME . ' no pudo ser actualizad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Eliminar fórmula
    public function destroy(string $id){

        try {

            $model = Formula::where('id', $id)->first();

            if (!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->status = 'eliminado';
                    $model->save();
                    
                    return SendResponse::message(true, 'destroy', self::NAME . ' fue eliminad' . self::GENDER . ' correctamente', null, 200);

                }

                return SendResponse::message(false, 'destroy', self::NAME . ' ya se encuentra eliminad' . self::GENDER, null, 400);

            }

            return SendResponse::message(false, 'destroy', self::NAME . ' ya se encuentra eliminad' . self::GENDER, null, 404);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'destroy', self::NAME . ' no pudo ser eliminad' . self::GENDER, $th->getMessage(), 500);

        }
        
    }

    //? Eliminar detalle de fórmula
    public function destroyDetails(string $id){

        try {

            $model = FormulaDetails::where('id', $id)->first();

            if (!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->status = 'eliminado';
                    $model->save();
                    
                    return SendResponse::message(true, 'destroy', self::NAME2 . ' fue eliminad' . self::GENDER2 . ' correctamente', null, 200);

                }

                return SendResponse::message(false, 'destroy', self::NAME2 . ' ya se encuentra eliminad' . self::GENDER2, null, 400);

            }

            return SendResponse::message(false, 'destroy', self::NAME2 . ' ya se encuentra eliminad' . self::GENDER2, null, 404);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'destroy', self::NAME2 . ' no pudo ser eliminad' . self::GENDER2, $th->getMessage(), 500);

        }
        
    }

    //? Reporte excel de la fórmula 
    public function formulaExcel(Request $request){

        $filters = $request->json()->all();

        $formula = Formula::where('id', $filters["id"])
                        ->where('status', '!=', 'eliminado')
                        ->with(['unitMeasure', 'details', 'detailsNucleos'])
                        ->first();

        // Crear una nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F81BD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER] // Centrado horizontal y vertical
        ];
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0070C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER] // Centrado horizontal y vertical
        ];
        $header2Style = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER] // Centrado horizontal y vertical
        ];
        $contentStyle = [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER], // Centrado horizontal y vertical
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]]
        ];

        // Título de la hoja
        $sheet->setCellValue('A1', 'Detalles de Fórmula');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->applyFromArray($titleStyle);

        // Detalles iniciales
        $sheet->setCellValue('A2', 'Nombre');
        $sheet->setCellValue('B2', $formula->name);
        $sheet->setCellValue('A3', 'Unidad de Medida');
        $sheet->setCellValue('B3', $formula->unitMeasure->name ?? 'Sin unidad');
        $sheet->setCellValue('A4', 'Total Macros');
        $sheet->setCellValue('B4', $formula->total_macros);
        $sheet->setCellValue('A5', 'Total Núcleos');
        $sheet->setCellValue('B5', $formula->total_nucleo);
        $sheet->setCellValue('A6', 'Costo Total');
        $sheet->setCellValue('B6', $formula->cost_total);

        // Estilo para las celdas de detalles
        $sheet->getStyle('A2:A6')->applyFromArray($headerStyle);
        $sheet->getStyle('B2:B6')->applyFromArray($header2Style);

        // Configurar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(25); // Columna A
        $sheet->getColumnDimension('B')->setWidth(30); // Columna B

        // Agregar detalles (insumos)
        $startRow = 8;
        $sheet->setCellValue('A' . $startRow, 'Detalles de Insumos');
        $sheet->mergeCells("A$startRow:B$startRow");
        $sheet->getStyle("A$startRow:B$startRow")->applyFromArray($titleStyle);

        $startRow++;
        $sheet->fromArray(['Producto', 'Cantidad'], null, "A$startRow");
        $sheet->getStyle("A$startRow:B$startRow")->applyFromArray($headerStyle);

        $row = $startRow + 1;
        foreach ($formula->details as $detail) {
            $sheet->setCellValue('A' . $row, $detail->product->name ?? 'Sin producto');
            $sheet->setCellValue('B' . $row, $detail->amount ?? 0);

            $sheet->getStyle('A'.$row)->applyFromArray($contentStyle);
            $sheet->getStyle('B'.$row)->applyFromArray($contentStyle);

            $row++;
        }

        // Agregar detalles (núcleos)
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Detalles de Núcleos');
        $sheet->mergeCells("A$row:B$row");
        $sheet->getStyle("A$row:B$row")->applyFromArray($titleStyle);

        $row++;
        $sheet->fromArray(['Producto', 'Cantidad'], null, "A$row");
        $sheet->getStyle("A$row:B$row")->applyFromArray($headerStyle);

        $row++;
        foreach ($formula->detailsNucleos as $detail) {
            $sheet->setCellValue('A' . $row, $detail->product->name ?? 'Sin producto');
            $sheet->setCellValue('B' . $row, $detail->amount ?? 0);

            $sheet->getStyle('A'.$row)->applyFromArray($contentStyle);
            $sheet->getStyle('B'.$row)->applyFromArray($contentStyle);

            $row++;
        }

        // Guardar el archivo
        $fileName = 'fomula_detalles.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Cache-Control' => 'max-age=0',
        ]);

    }

}
