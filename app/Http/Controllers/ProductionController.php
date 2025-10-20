<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Productions\Production;
use App\Models\Productions\ProductionDetails;
use App\Models\Formulas\Formula;
use App\Models\Products\Product;
use App\Models\Products\ProductStockHistory;
use App\Models\Maestras\Process;
use App\Models\Maestras\Shift;
use App\Models\Maestras\UnitMeasure;
use App\Models\Maestras\UnitMeasureConvertion;
use App\Http\Resources\Productions\ProductionResource;
use App\Http\Resources\Productions\ProductionsCollection;
use App\Http\Resources\Productions\ProductionsDetailsCollection;
use App\Http\Requests\Productions\ProductionRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Symfony\Component\HttpFoundation\Response;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;


class ProductionController extends Controller{

    const NAME      = 'La producción';
    const GENDER    = 'a';

    //? Listar producciones
    public function index(Request $request){

        $filters = $request->all();
        $data = $this->getProductions($filters);

        return $data;

    }

    //? Guardar producción
    public function store(ProductionRequest $request){

        try {

            DB::beginTransaction();

            $lastSale = Production::latest('consecutive')->first();

            if ($lastSale) {
                $lastConsecutive = intval($lastSale->consecutive);
                $newConsecutive = str_pad($lastConsecutive + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $newConsecutive = '000001';
            }

            $model = Production::create([
                'consecutive'      => $newConsecutive,
                'date'             => now(),
                'user_id'          => $request->user_id,
                'product_id'       => $request->product_id,
                'tons_produced'    => $request->tons_produced,
                'shift_id'         => $request->shift_id,
                'machine_id'       => $request->machine_id,
                'formula_id'       => $request->formula_id,
                'packing'          => $request->packing,
                'amount'           => $request->amount,
                'observations'     => $request->observations ?? "",
            ]);

            //? Registramos todos los productos que conforman la formula seleccionada
            $formula = Formula::where('id', $request->formula_id)->with('details', 'detailsNucleos')->first();

            if ($formula) {

                $product = Product::where('id_formula', $formula->id)->where('status', '!=', 'eliminado')->first();

                if (!$product) {
                    return SendResponse::message(false, 'store', 'Producto asocidado con la fórmula no encontrado', null, 404);
                }

                //? Obtengo mi stock en kg
                $modelUnitMeasure = UnitMeasure::where('id', $product->id_unit_measure)->where('status', '!=', 'eliminado')->first();
                $stock = 0;
                $convertedStock = 0;

                if($modelUnitMeasure->slug == "kg"){
                    $newStock = $product->converted_stock + ($request->amount * $request->tons_produced);
                    $convertedStock  = number_format($newStock * $product->equivalent, 4, '.', '');

                    $convertedStockIncrease = $newStock - $product->converted_stock;
                    $stockIncrease = $convertedStock - $product->stock;

                    //? Registrar historial
                    ProductStockHistory::create([
                        'product_id'        => $product->id,
                        'stock'             => $stockIncrease,
                        'converted_stock'   => $convertedStockIncrease,
                        'date'              => now(),
                        'type'              => "produccion_aumento"
                    ]);

                    $product->update(['stock' => $convertedStock, 'converted_stock' => $newStock]);
                } else {
                    $newStock = $product->stock + ($request->amount * $request->tons_produced);
                    $convertedStock  = number_format($newStock * $product->equivalent, 4, '.', '');

                    $stockIncrease = $newStock - $product->stock;
                    $convertedStockIncrease = $convertedStock - $product->converted_stock;

                    //? Registrar historial
                    ProductStockHistory::create([
                        'product_id'        => $product->id,
                        'stock'             => $stockIncrease, 
                        'converted_stock'   => $convertedStockIncrease,
                        'date'              => now(),
                        'type'              => "produccion_aumento"
                    ]);

                    $product->update(['stock' => $newStock, 'converted_stock' => $convertedStock]);
                }

                //? Descontamos de los detalles de la fórmula
                $detailsFormula = $formula->details->merge($formula->detailsNucleos);

                $productIds = $detailsFormula->pluck('product_id')->toArray();
                $products = Product::whereIn('id', $productIds)
                                    ->where('status', '!=', 'eliminado')
                                    ->with('process', 'unitMeasure')
                                    ->get()
                                    ->keyBy('id');

                /*
                    Validamos que haya stock suficiente para cada producto en los detalles
                */

                foreach ($detailsFormula as $detail) {

                    $product = $products->get($detail['product_id']);
                    $multiplier = $request->tons_produced;

                    if (!$product) {
                        return SendResponse::message(false, 'store', 'Producto no encontrado', null, 404);
                    }

                    if($product->unitMeasure->slug == "kg"){
                        $stock = ($product->stock * $multiplier) - $detail["amount"];
                    } else {
                        $stock = ($product->converted_stock * $multiplier) - $detail["amount"];
                    }

                    if($detail['amount'] > $stock){
                        return SendResponse::message(false, 'store', $product["name"] . ' no cuenta con stock suficiente, por favor elegir una cantidad menor', null, 500);
                    }

                }

                /*
                    Registramos los detalles y actualizamos el stock de los productos
                */

                foreach ($detailsFormula as $detail) {

                    $product = $products->get($detail['product_id']);
                    $multiplier = $request->tons_produced;

                    ProductionDetails::create([
                        'id_production'  => $model['id'],
                        'id_product'     => $detail['product_id'],
                        'cod_product'    => $product['cod_product'],
                        'name'           => $product['name'],
                        'id_process'     => null,
                        'process'        => "",
                        'presentation'   => null, //$request->id_presentation,
                        'unit_measure'   => $product['unitMeasure']['name'],
                        'price'          => $detail['price'],
                        'amount'         => $detail['amount'],
                        'type'           => "formula",
                    ]);

                    if($product->unitMeasure->slug == "kg"){
                        $newStock = ($product->stock * $multiplier) - $detail["amount"];
                        $convertedStock  = number_format($newStock / $product->equivalent, 4, '.', '');

                        $stockDecrease = $product->stock - $newStock;
                        $convertedStockDecrease = $product->converted_stock - $convertedStock;

                        ProductStockHistory::create([
                            'product_id'        => $product->id,
                            'stock'             => $stockDecrease,
                            'converted_stock'   => $convertedStockDecrease,
                            'date'              => now(),
                            'type'              => "produccion_disminuye"
                        ]);

                        $product->update(['stock' => $newStock, 'converted_stock' => $convertedStock]);
                    } else {
                        $newStock = ($product->converted_stock * $multiplier) - $detail["amount"];
                        $convertedStock  = number_format($newStock / $product->equivalent, 4, '.', '');

                        $stockDecrease = $product->stock - $convertedStock;
                        $convertedStockDecrease = $product->converted_stock - $newStock;

                        ProductStockHistory::create([
                            'product_id'        => $product->id,
                            'stock'             => $stockDecrease,
                            'converted_stock'   => $convertedStockDecrease,
                            'date'              => now(),
                            'type'              => "produccion_disminuye"
                        ]);

                        $product->update(['stock' => $convertedStock, 'converted_stock' => $newStock]);
                    }

                }

            }

            DB::commit();
            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new ProductionResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Guardar producción del cliente
    public function store_client(ProductionRequest $request){

        try {

            DB::beginTransaction();

            $lastSale = Production::latest('consecutive')->first();

            if ($lastSale) {
                $lastConsecutive = intval($lastSale->consecutive);
                $newConsecutive = str_pad($lastConsecutive + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $newConsecutive = '000001';
            }

            $model = Production::create([
                'client_id'        => $request->client_id ?? null,
                'consecutive'      => $newConsecutive,
                'date'             => now(),
                'user_id'          => $request->user_id,
                'product_id'       => $request->product_id,
                'tons_produced'    => $request->tons_produced,
                'shift_id'         => $request->shift_id,
                'machine_id'       => $request->machine_id,
                'formula_id'       => $request->formula_id,
                'packing'          => $request->packing,
                'amount'           => $request->amount,
                'observations'     => $request->observations ?? "",
                'type'             => "preparada",
            ]);

            //? Registramos todos los productos que conforman la formula seleccionada
            $formula = Formula::where('id', $request->formula_id)->with('details', 'detailsNucleos')->first();

            if ($formula) {

                //? Descontamos de los detalles de la fórmula
                $detailsFormula = $formula->details->merge($formula->detailsNucleos);

                $productIds = $detailsFormula->pluck('product_id')->toArray();
                $products = Product::whereIn('id', $productIds)
                                    ->where('status', '!=', 'eliminado')
                                    ->with('process', 'unitMeasure')
                                    ->get()
                                    ->keyBy('id');


                /*
                    Registramos los detalles y actualizamos el stock de los productos
                */

                foreach ($detailsFormula as $detail) {

                    $product = $products->get($detail['product_id']);

                    ProductionDetails::create([
                        'id_production'  => $model['id'],
                        'id_product'     => $detail['product_id'],
                        'cod_product'    => $product['cod_product'],
                        'name'           => $product['name'],
                        'id_process'     => null,
                        'process'        => "",
                        'presentation'   => null, //$request->id_presentation,
                        'unit_measure'   => $product['unitMeasure']['name'],
                        'price'          => $detail['price'],
                        'amount'         => $detail['amount'],
                        'type'           => "formula",
                    ]);

                }

            }

            DB::commit();
            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new ProductionResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Ver producción
    public function show($id){

        try {

            $model = Production::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new ProductionResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar producción
    public function update(ProductionRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = Production::where('id', $id)->with('details')->first();

            if (empty($model)) {
                return SendResponse::message(false, 'update', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            }

            if ($model->status == 'eliminado') {
                return SendResponse::message(false, 'update', self::NAME . ' ya se encuentra eliminad' . self::GENDER, null, 400);
            }

            $FormulaIdOld = $request->formula_id;

            // Actualizamos la producción
            $model->update([
                'product_id'       => $request->product_id,
                'tons_produced'    => $request->tons_produced,
                'shift_id'         => $request->shift_id,
                'machine_id'       => $request->machine_id,
                'formula_id'       => $request->formula_id,
                'packing'          => $request->packing,
                'amount'           => $request->amount,
                'observations'     => $request->observations ?? "",
            ]);

            $model->refresh();

            if($FormulaIdOld != $request->formula_id){

                //? Registramos todos los productos que conforman la formula seleccionada
                $formula = Formula::where('id', $request->formula_id)->with('details', 'detailsNucleos')->first();

                if ($formula) {

                    ProductionDetails::where('id_production', $id)->delete();

                    // $product = Product::where('id_formula', $formula->id)->where('status', '!=', 'eliminado')->first();

                    // if (!$product) {
                    //     return SendResponse::message(false, 'store', 'Producto asocidado con la fórmula no encontrado', null, 404);
                    // }

                    // //? Obtengo mi stock en kg
                    // $modelUnitMeasure = UnitMeasure::where('id', $product->id_unit_measure)->where('status', '!=', 'eliminado')->first();
                    // $stock = 0;
                    // $convertedStock = 0;

                    // if($modelUnitMeasure->slug == "kg"){
                    //     $newStock = $product->converted_stock + ($request->amount * $request->tons_produced);
                    //     $convertedStock  = number_format($newStock * $product->equivalent, 4, '.', '');
                    //     $product->update(['stock' => $convertedStock, 'converted_stock' => $newStock]);
                    // } else {
                    //     $newStock = $product->stock + ($request->amount * $request->tons_produced);
                    //     $convertedStock  = number_format($newStock * $product->equivalent, 4, '.', '');
                    //     $product->update(['stock' => $newStock, 'converted_stock' => $convertedStock]);
                    // }

                    //? Descontamos de los detalles de la fórmula
                    $detailsFormula = $formula->details->merge($formula->detailsNucleos);

                    $productIds = $detailsFormula->pluck('product_id')->toArray();
                    $products = Product::whereIn('id', $productIds)
                                        ->where('status', '!=', 'eliminado')
                                        ->with('process', 'unitMeasure')
                                        ->get()
                                        ->keyBy('id');

                    /*
                        Validamos que haya stock suficiente para cada producto en los detalles
                    */

                    foreach ($detailsFormula as $detail) {

                        $product = $products->get($detail['product_id']);
                        $multiplier = $request->tons_produced;

                        if (!$product) {
                            return SendResponse::message(false, 'store', 'Producto no encontrado', null, 404);
                        }

                        if($product->unitMeasure->slug == "kg"){
                            $stock = ($product->stock * $multiplier) - $detail["amount"];
                        } else {
                            $stock = ($product->converted_stock * $multiplier) - $detail["amount"];
                        }

                        if($detail['amount'] > $stock){
                            return SendResponse::message(false, 'store', $product["name"] . ' no cuenta con stock suficiente, por favor elegir una cantidad menor', null, 500);
                        }

                    }

                    /*
                        Registramos los detalles y actualizamos el stock de los productos
                    */

                    foreach ($detailsFormula as $detail) {

                        $product = $products->get($detail['product_id']);
                        $multiplier = $request->tons_produced;

                        ProductionDetails::create([
                            'id_production'  => $id,
                            'id_product'     => $detail['product_id'],
                            'cod_product'    => $product['cod_product'],
                            'name'           => $product['name'],
                            'id_process'     => null,
                            'process'        => "",
                            'presentation'   => null, //$request->id_presentation,
                            'unit_measure'   => $product['unitMeasure']['name'],
                            'price'          => $detail['price'],
                            'amount'         => $detail['amount'],
                            'type'           => "formula",
                        ]);

                        if($product->unitMeasure->slug == "kg"){
                            $newStock = ($product->stock * $multiplier) - $detail["amount"];
                            $convertedStock  = number_format($newStock / $product->equivalent, 4, '.', '');

                            $stockDecrease = $product->stock - $newStock;
                            $convertedStockDecrease = $product->converted_stock - $convertedStock;

                            ProductStockHistory::create([
                                'product_id'        => $product->id,
                                'stock'             => $stockDecrease,
                                'converted_stock'   => $convertedStockDecrease,
                                'date'              => now(),
                                'type'              => "produccion_disminuye"
                            ]);

                            $product->update(['stock' => $newStock, 'converted_stock' => $convertedStock]);
                        } else {
                            $newStock = ($product->converted_stock * $multiplier) - $detail["amount"];
                            $convertedStock  = number_format($newStock / $product->equivalent, 4, '.', '');

                            $stockDecrease = $product->stock - $convertedStock;
                            $convertedStockDecrease = $product->converted_stock - $newStock;

                            ProductStockHistory::create([
                                'product_id'        => $product->id,
                                'stock'             => $stockDecrease,
                                'converted_stock'   => $convertedStockDecrease,
                                'date'              => now(),
                                'type'              => "produccion_disminuye"
                            ]);

                            $product->update(['stock' => $convertedStock, 'converted_stock' => $newStock]);
                        }

                    }

                }

            }

            DB::commit();
            return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new ProductionResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'update', self::NAME . ' no pudo ser actualizad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Editar producción del cliente
    public function update_client(ProductionRequest $request, string $id){

        try {

            DB::beginTransaction();
            $model = Production::where('id', $id)->with('details')->first();

            if (empty($model)) {
                return SendResponse::message(false, 'update', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            }

            if ($model->status == 'eliminado') {
                return SendResponse::message(false, 'update', self::NAME . ' ya se encuentra eliminad' . self::GENDER, null, 400);
            }

            $FormulaIdOld = $request->formula_id;

            // Actualizamos la producción
            $model->update([
                'client_id'        => $request->client_id ?? null,
                'product_id'       => $request->product_id,
                'tons_produced'    => $request->tons_produced,
                'shift_id'         => $request->shift_id,
                'machine_id'       => $request->machine_id,
                'formula_id'       => $request->formula_id,
                'packing'          => $request->packing,
                'amount'           => $request->amount,
                'observations'     => $request->observations ?? "",
            ]);

            $model->refresh();

            if($FormulaIdOld != $request->formula_id){

                //? Registramos todos los productos que conforman la formula seleccionada
                $formula = Formula::where('id', $request->formula_id)->with('details', 'detailsNucleos')->first();

                if ($formula) {

                    ProductionDetails::where('id_production', $id)->delete();

                    //? Descontamos de los detalles de la fórmula
                    $detailsFormula = $formula->details->merge($formula->detailsNucleos);

                    $productIds = $detailsFormula->pluck('product_id')->toArray();
                    $products = Product::whereIn('id', $productIds)
                                        ->where('status', '!=', 'eliminado')
                                        ->with('process', 'unitMeasure')
                                        ->get()
                                        ->keyBy('id');

                    /*
                        Registramos los detalles y actualizamos el stock de los productos
                    */

                    foreach ($detailsFormula as $detail) {

                        $product = $products->get($detail['product_id']);

                        ProductionDetails::create([
                            'id_production'  => $id,
                            'id_product'     => $detail['product_id'],
                            'cod_product'    => $product['cod_product'],
                            'name'           => $product['name'],
                            'id_process'     => $product['process']['id'],
                            'process'        => $product['process']['name'],
                            'presentation'   => null, //$request->id_presentation,
                            'unit_measure'   => $product['unitMeasure']['name'],
                            'price'          => $detail['price'],
                            'amount'         => $detail['amount'],
                            'type'           => "formula",
                        ]);

                    }

                }

            }

            DB::commit();
            return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new ProductionResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'update', self::NAME . ' no pudo ser actualizad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Eliminar producción
    public function destroy(string $id){

        try {

            $model = Production::where('id', $id)->first();

            if (!empty($model)){

                if ($model->status != 'eliminado') {

                    //? Para devolver la data
                    $formula = Formula::where('id', $model->formula_id)->with('details', 'detailsNucleos')->first();


                    //? Devolvemos el stock del producto
                    $productFormula = Product::where('id_formula', $formula->id)->first();
                    $newStockProductFormula = $productFormula->stock - ($model->amount * $model->tons_produced);
                    $productFormula->update(['stock' => $newStockProductFormula]);


                    //? Descontamos de los detalles de la fórmula
                    $detailsFormula = $formula->details->merge($formula->detailsNucleos);

                    $productIds = $detailsFormula->pluck('product_id')->toArray();
                    $products = Product::whereIn('id', $productIds)
                                        ->where('status', '!=', 'eliminado')
                                        ->with('process', 'unitMeasure')
                                        ->get()
                                        ->keyBy('id');

                    foreach ($detailsFormula as $detail) {

                        $product = $products->get($detail['product_id']);
                        $multiplier = $model->tons_produced;

                        if($product->unitMeasure->slug == "kg"){
                            $newStock = ($product->stock * $multiplier) + $detail["amount"];
                            $convertedStock  = number_format($newStock / $product->equivalent, 4, '.', '');

                            $stockIncrease = $newStock - $product->stock;
                            $convertedStockIncrease = $convertedStock - $product->converted_stock;

                            ProductStockHistory::create([
                                'product_id'        => $product->id,
                                'stock'             => $stockIncrease,
                                'converted_stock'   => $convertedStockIncrease,
                                'date'              => now(),
                                'type'              => "produccion_aumento"
                            ]);

                            $product->update(['stock' => $newStock, 'converted_stock' => $convertedStock]);
                        } else {
                            $newStock = ($product->converted_stock * $multiplier) + $detail["amount"];
                            $convertedStock  = number_format($newStock / $product->equivalent, 4, '.', '');

                            $stockIncrease = $convertedStock - $product->stock;
                            $convertedStockIncrease = $newStock - $product->converted_stock;

                            ProductStockHistory::create([
                                'product_id'        => $product->id,
                                'stock'             => $stockIncrease,
                                'converted_stock'   => $convertedStockIncrease,
                                'date'              => now(),
                                'type'              => "produccion_aumento"
                            ]);

                            $product->update(['stock' => $convertedStock, 'converted_stock' => $newStock]);
                        }

                    }

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

    //? Excel de producciones
    public function excelProductions(Request $request){

        $filters = $request->json()->all();
        $data = $this->getProductions($filters);

        // Crear el archivo Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar el título y fusionar celdas
        $sheet->setCellValue('A1', 'Listado de Producciones');
        $sheet->mergeCells('A1:I1');

        // Estilo para el título
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4F81BD'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($titleStyle);
        $sheet->getRowDimension('1')->setRowHeight(30);

        // Encabezados
        $headers = [
            'N. de Producción', 'Año', 'Fecha', 'Mes', 'TN', 'Turno', 'Máquina', 'Fórmula', 'Observaciones'
        ];
        $sheet->fromArray($headers, null, 'A2');

        // Estilo para los encabezados
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A2:I2')->applyFromArray($headerStyle);

        // Ajustar el ancho de las columnas
        $columnWidths = [20, 10, 15, 10, 10, 10, 20, 25, 30];
        foreach (range('A', 'I') as $index => $column) {
            $sheet->getColumnDimension($column)->setWidth($columnWidths[$index]);
        }

        // Agregar los datos de las producciones
        $row = 3;
        foreach ($data as $production) {
            $date = \Carbon\Carbon::parse($production->date);
            $sheet->setCellValue('A' . $row, $production->consecutive); // Número de Producción
            $sheet->setCellValue('B' . $row, $date->year); // Año
            $sheet->setCellValue('C' . $row, $date->format('d/m/y')); // Fecha
            $sheet->setCellValue('D' . $row, $date->month); // Mes
            $sheet->setCellValue('E' . $row, $production->tons_produced); // TN
            $sheet->setCellValue('F' . $row, $production->shift->name ?? ""); // Turno
            $sheet->setCellValue('G' . $row, $production->machine->name ?? ""); // Máquina
            $sheet->setCellValue('H' . $row, $production->formula->name ?? ""); // Fórmula
            $sheet->setCellValue('I' . $row, $production->observations); // Observaciones
            $row++;
        }

        // Aplicar filtros automáticos
        $sheet->setAutoFilter('A2:I2');

        // Generar el archivo Excel para descargar
        $writer = new Xlsx($spreadsheet);
        $fileName = 'reporte_producciones.xlsx';

        return response()->stream(function() use ($writer) {
            $writer->save('php://output');
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
            'Expires' => '0',
        ]);

    }

    //? Excel de insumos de producciones
    public function excelDetailsProductions(Request $request){

        $filters = $request->json()->all();
        $data = $this->getProductionsDetails($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Listado de Producciones');
        $sheet->mergeCells('A1:I1');

        // Estilo para el título
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4F81BD'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // Aplicar el estilo al título
        $sheet->getStyle('A1:I1')->applyFromArray($titleStyle);
        $sheet->getRowDimension('1')->setRowHeight(30); // Ajustar la altura de la fila del título

        // Agregar los encabezados en la segunda fila
        $headers = ['Año', 'Fecha', 'Mes', 'Producto', 'Código', 'TN', 'Turno', 'Máquina', 'Observaciones'];
        // $headers = ['Año', 'Fecha', 'Mes', 'Producto', 'Código', 'TN', 'Proceso', 'Turno', 'Máquina', 'Observaciones'];
        $sheet->fromArray($headers, null, 'A2');

        // Estilo para los encabezados
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'], // Color de texto (blanco)
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'], // Color de fondo (azul)
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Centrar el texto
            ],
        ];

        // Aplicar el estilo a los encabezados
        $sheet->getStyle('A2:I2')->applyFromArray($headerStyle);

        // Ajustar el ancho de las columnas
        $columnWidths = [10, 15, 10, 20, 15, 10, 20, 10, 15, 25];
        foreach (range('A', 'I') as $index => $column) {
            $sheet->getColumnDimension($column)->setWidth($columnWidths[$index]);
        }

        $sheet->setAutoFilter('A2:I2');

        $row = 3;
        foreach ($data as $detail) {

            $production = $detail->production;
            $date = \Carbon\Carbon::parse($production->date);

            $sheet->setCellValue('A' . $row, $date->year);
            $sheet->setCellValue('B' . $row, $date->format('d/m/y'));
            $sheet->setCellValue('C' . $row, $date->month);
            $sheet->setCellValue('D' . $row, $detail->name);
            $sheet->setCellValue('E' . $row, $detail->cod_product);
            $sheet->setCellValue('F' . $row, $production->tons_produced);
            // $sheet->setCellValue('G' . $row, $detail->process);
            $sheet->setCellValue('G' . $row, $production->shift->name ?? "");
            $sheet->setCellValue('H' . $row, $production->machine->name ?? "");
            $sheet->setCellValue('I' . $row, $production->observations);
            $row++;

        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'reporte_producciones.xlsx';

        return response()->stream(function() use ($writer) {
            $writer->save('php://output');
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public', // Asegurar la correcta caché
            'Expires' => '0', // No permitir que el archivo se almacene en caché
        ]);

    }

    //? Excel de producciones x producto
    public function excelProductionsProducts(Request $request){

        $filters = $request->json()->all();
        $data = $this->getProductionsDetails($filters);

        // Crear una nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Listado de Producciones x Productos');
        $sheet->mergeCells('A1:B1');

        // Estilo para el título
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4F81BD'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // Aplicar el estilo al título
        $sheet->getStyle('A1:C1')->applyFromArray($titleStyle);
        $sheet->getRowDimension('1')->setRowHeight(30);

        // Agregar los encabezados en la segunda fila
        $headers = ['Fecha', 'Producto', 'TN'];
        $sheet->fromArray($headers, null, 'A2');

        // Estilo para los encabezados
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];

        // Aplicar el estilo a los encabezados
        $sheet->getStyle('A2:C2')->applyFromArray($headerStyle);

        // Ajustar el ancho de las columnas
        $columnWidths = [30, 30, 20];
        foreach (range('A', 'C') as $index => $column) {
            $sheet->getColumnDimension($column)->setWidth($columnWidths[$index]);
        }

        $sheet->setAutoFilter('A2:C2');

        $row = 3;
        $cont = 0;
        $dataStyle = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        foreach ($data as $detail) {

            $production = $detail->production;

            $date = \Carbon\Carbon::parse($production->date);

            $sheet->setCellValue('A' . $row, $date->format('d/m/y'));
            $sheet->setCellValue('B' . $row, $detail->name);
            $sheet->setCellValue('C' . $row, $production->tons_produced);
            $sheet->getStyle('A' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('C' . $row)->applyFromArray($dataStyle);

            $row++;
            $cont += $production->tons_produced;

        }

        $sheet->setCellValue('B' . $row, 'Total General');
        $sheet->setCellValue('C' . $row, $cont);

        $sheet->getStyle('B' . $row)->applyFromArray($headerStyle);
        $sheet->getStyle('C' . $row)->applyFromArray($headerStyle);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'reporte_producciones_x_productos.xlsx';

        return response()->stream(function() use ($writer) {
            $writer->save('php://output');
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public', // Asegurar la correcta caché
            'Expires' => '0', // No permitir que el archivo se almacene en caché
        ]);

    }

    //? Excel de producciones x proceso
    public function excelProductionsProcesses(Request $request){

        $filters = $request->json()->all();

        // Obtener las producciones agrupadas por fecha y proceso
        $data = ProductionDetails::where('productions_details.status', '!=', 'eliminado')
                                ->when(isset($filters['type']) && isset($filters['type']), function ($query) use ($filters) {
                                    return $query->whereHas('production', function ($q) use ($filters) {
                                        $q->where('type', $filters['type']);
                                    });
                                })
                                ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                                    return $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                                })
                                ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($q) use ($filters) {
                                    return $q->where('date', $filters['date']);
                                })
                                ->when(isset($filters['product']) && !empty($filters['product']), function ($query) use ($filters) {
                                    return $query->whereHas('product', function ($q) use ($filters) {
                                        $q->where('name', 'like', '%' . $filters['product'] . '%');
                                    });
                                })
                                ->with(['production', 'product.process'])
                                ->selectRaw('DATE(production.date) as production_date, id_process, SUM(tons_produced) as total_tons')
                                ->join('productions as production', 'production.id', '=', 'productions_details.id_production')
                                ->groupBy('production_date', 'id_process')
                                ->orderBy('production_date', 'DESC')
                                ->get();

        // Crear una nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Título de la hoja
        $sheet->setCellValue('A1', 'Suma de Toneladas por Fecha y Proceso');
        $sheet->mergeCells('A1:C1');

        // Estilo para el título
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4F81BD'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // Aplicar estilo al título
        $sheet->getStyle('A1:C1')->applyFromArray($titleStyle);
        $sheet->getRowDimension('1')->setRowHeight(30);

        // Encabezados
        $headers = ['Fecha', 'Turno', 'Suma de ton. por proceso'];
        $sheet->fromArray($headers, null, 'A2');

        // Estilo para los encabezados
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];

        // Aplicar estilo a los encabezados
        $sheet->getStyle('A2:C2')->applyFromArray($headerStyle);

        // Ajustar el ancho de las columnas
        $columnWidths = [30, 20, 25];
        foreach (range('A', 'C') as $index => $column) {
            $sheet->getColumnDimension($column)->setWidth($columnWidths[$index]);
        }

        $sheet->setAutoFilter('A2:C2');

        // Llenar los datos de la hoja de cálculo
        $row = 3; // Comenzar desde la fila 3
        foreach ($data as $detail) {
            $process = Process::find($detail->id_process); // Cambiar según tu lógica para obtener el turno

            $date = \Carbon\Carbon::parse($detail->production_date);
            $sheet->setCellValue('A' . $row, $date->format('d/m/y'));
            $sheet->setCellValue('B' . $row, $process->name ?? ""); // Cambiar según tu lógica para obtener el nombre del turno
            $sheet->setCellValue('C' . $row, $detail->total_tons);

            $row++;
        }

        // Crear el escritor y enviar el archivo como respuesta
        $writer = new Xlsx($spreadsheet);
        $fileName = 'reporte_producciones_por_fecha_y_turno.xlsx';

        return response()->stream(function() use ($writer) {
            $writer->save('php://output');
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
            'Expires' => '0',
        ]);

    }

    //? Excel de producciones x turno
    public function excelProductionsShifts(Request $request){

        $filters = $request->json()->all();
        $data = ProductionDetails::selectRaw('
                                        DATE(production.date) as production_date, 
                                        production.shift_id, 
                                        SUM(production.tons_produced) as total_tons
                                    ')
                                    ->join('productions as production', 'production.id', '=', 'productions_details.id_production')
                                    ->where('productions_details.status', '!=', 'eliminado')
                                    ->when(isset($filters['type']) && isset($filters['type']), function ($query) use ($filters) {
                                        return $query->whereHas('production', function ($q) use ($filters) {
                                            $q->where('type', $filters['type']);
                                        });
                                    })
                                    ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($query) use ($filters) {
                                        return $query->whereHas('production', function ($q) use ($filters) {
                                            $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                                        });
                                    })
                                    ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($query) use ($filters) {
                                        return $query->whereHas('production', function ($q) use ($filters) {
                                            $q->where('date', $filters['date']);
                                        });
                                    })
                                    ->when(!empty($filters['product']), function ($query) use ($filters) {
                                        return $query->where('name', 'like', '%' . $filters['product'] . '%');
                                    })
                                    ->groupBy('production_date', 'production.shift_id') // Agrupar por fecha de producción y ID del turno
                                    ->orderBy('production_date', 'ASC') // Ordenar por fecha
                                    ->orderBy('production.shift_id', 'ASC') // Ordenar por ID de turno
                                    ->get();

        // Crear una nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Sum de Toneladas por Fecha y Turno');
        $sheet->mergeCells('A1:C1');

        // Estilo para el título
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['argb' => 'FFFFFFFF'], // Color de texto (blanco)
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4F81BD'], // Color de fondo (azul oscuro)
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Centrar el texto
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Centrar verticalmente
            ],
        ];

        // Aplicar el estilo al título
        $sheet->getStyle('A1:C1')->applyFromArray($titleStyle);
        $sheet->getRowDimension('1')->setRowHeight(30); // Ajustar la altura de la fila del título

        // Agregar los encabezados en la segunda fila
        $headers = ['Fecha', 'Turno', 'Suma de ton. por turno'];
        $sheet->fromArray($headers, null, 'A2');

        // Estilo para los encabezados
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'], // Color de texto (blanco)
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'], // Color de fondo (azul)
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Centrar el texto
            ],
        ];

        // Aplicar el estilo a los encabezados
        $sheet->getStyle('A2:C2')->applyFromArray($headerStyle);

        // Ajustar el ancho de las columnas
        $columnWidths = [30, 20, 25];
        foreach (range('A', 'C') as $index => $column) {
            $sheet->getColumnDimension($column)->setWidth($columnWidths[$index]);
        }

        $sheet->setAutoFilter('A2:C2');

        $row = 3; // Comenzar desde la fila 3
        foreach ($data as $detail) {

            $shift = Shift::find($detail->shift_id);

            $date = \Carbon\Carbon::parse($detail->production_date);
            $sheet->setCellValue('A' . $row, $date->format('d/m/y'));
            $sheet->setCellValue('B' . $row, $shift->name ?? "");
            $sheet->setCellValue('C' . $row, $detail->total_tons);

            $row++;

        }

        // Crear el escritor y enviar el archivo como respuesta
        $writer = new Xlsx($spreadsheet);
        $fileName = 'reporte_producciones_por_fecha_y_turno.xlsx';

        return response()->stream(function() use ($writer) {
            $writer->save('php://output');
        }, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public', // Asegurar la correcta caché
            'Expires' => '0', // No permitir que el archivo se almacene en caché
        ]);

    }

    private function getProductionsDetails($filters){

        $data = ProductionDetails::where('status', '!=', 'eliminado')
                                ->when(isset($filters['product']) && !empty($filters['product']), function ($q) use ($filters) {
                                    return $q->where('name', 'like', '%' . $filters['product'] . '%');
                                })
                                ->when(isset($filters['type']) && isset($filters['type']), function ($query) use ($filters) {
                                    return $query->whereHas('production', function ($q) use ($filters) {
                                        $q->where('type', $filters['type']);
                                    });
                                })
                                ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($query) use ($filters) {
                                    return $query->whereHas('production', function ($q) use ($filters) {
                                        $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                                    });
                                })
                                ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($query) use ($filters) {
                                    return $query->whereHas('production', function ($q) use ($filters) {
                                        $q->where('date', $filters['date']);
                                    });
                                })
                                ->get();

        return new ProductionsDetailsCollection($data);

    } 

    private function getProductions($filters){

        $data = Production::where('status', '!=', 'eliminado')
                            ->when(isset($filters['type']) && isset($filters['type']) && !empty($filters['type']) && !empty($filters['type']), function ($q) use ($filters) {
                                return $q->where('type', $filters['type']);
                            })
                            ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                                return $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                            })
                            ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($q) use ($filters) {
                                return $q->where('date', $filters['date']);
                            })
                            ->when(isset($filters['formula']) && !empty($filters['formula']), function ($query) use ($filters) {
                                return $query->whereHas('formula', function ($q) use ($filters) {
                                    $q->where('name', 'like', '%' . $filters['formula'] . '%');
                                });
                            })
                            ->get();

        return new ProductionsCollection($data);

    } 

}
