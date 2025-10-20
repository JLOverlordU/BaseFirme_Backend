<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Str;

use App\Models\Sales\Sale;
use App\Models\Sales\SaleDetails;
use App\Models\Purchases\PurchaseDetails;
use App\Models\Sales\SaleDepositsHistory;
use App\Models\Products\Product;
use App\Models\Products\ProductStockHistory;
use App\Models\Maestras\UnitMeasureConvertion;
use App\Http\Resources\Sales\SalesCollection;
use App\Http\Resources\Sales\SalesDetailsCollection;
use App\Http\Resources\Sales\SalesPurchasesDetailsCollection;
use App\Http\Resources\Sales\SalesDepositsHistoryCollection;
use App\Http\Resources\Sales\SaleResource;
use App\Http\Resources\Sales\SaleDepositsHistoryResource;
use App\Http\Requests\Sales\SaleRequest;
use App\Http\Requests\Sales\SaleDepositRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Symfony\Component\HttpFoundation\Response;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller{

    const NAME      = 'La venta';
    const GENDER    = 'a';
    const NAME2     = 'El detalle';
    const GENDER2   = 'o';
    const NAME3     = 'El deposito';

    //? Listar ventas
    public function index(){

        $data = Sale::where('status', '!=', 'eliminado')->get();

        return new SalesCollection($data);

    }

    //? Listar ventas con filtros
    public function getSales(Request $request){

        $filters = $request->all();

        $data = Sale::where('status', '!=', 'eliminado')
                        ->when(isset($filters['consecutive']) && !empty($filters['consecutive']), function ($q) use ($filters) {
                            return $q->where('consecutive', $filters['consecutive']);
                        })
                        ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                            return $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                        })
                        ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($q) use ($filters) {
                            return $q->where('date', $filters['date']);
                        })
                        ->when(isset($filters['client']) && !empty($filters['client']), function ($query) use ($filters) {
                            return $query->whereHas('client', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['client'] . '%');
                            });
                        })
                        ->when(isset($filters['user']) && !empty($filters['user']), function ($query) use ($filters) {
                            return $query->whereHas('user', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['user'] . '%');
                            });
                        })
                        ->when(isset($filters['type']) && !empty($filters['type']) && ($filters['type'] != "ambas"), function ($q) use ($filters) {
                            return $q->where('type', $filters['type']);
                        })
                        ->get();

        return new SalesCollection($data);

    }

    //? Listado de detalles de las ventas
    public function getSalesDetails(Request $request){

        $filters = $request->all();

        $data = $this->getSalesPurchasesDetails($filters);

        return new SalesPurchasesDetailsCollection($data);

    }

    //? Listar ventas por cliente
    public function getClientBySales(Request $request){

        $filters = $request->all();

        $data = Sale::where('status', '!=', 'eliminado')
                        ->where('type', 'credito')
                        ->when(isset($filters['type']) && $filters['type'] !== 'ambas', function ($q) use ($filters) {
                            if ($filters['type'] === 'pendientes') {
                                return $q->whereColumn('subtotal', '!=', 'deposit');
                            } elseif ($filters['type'] === 'finalizadas') {
                                return $q->whereColumn('subtotal', '=', 'deposit');
                            }
                        })
                        ->when(isset($filters['consecutive']) && !empty($filters['consecutive']), function ($q) use ($filters) {
                            return $q->where('consecutive', $filters['consecutive']);
                        })
                        ->when(isset($filters['client']) && !empty($filters['client']), function ($query) use ($filters) {
                            return $query->whereHas('client', function ($q) use ($filters) {
                                $q->where('id', $filters['client']);
                            });
                        })
                        ->get();

        return new SalesCollection($data);

    }

    //? Guardar historial de pago
    public function saveDepositsHistory(SaleDepositRequest $request){

        try {

            DB::beginTransaction();

            $model = SaleDepositsHistory::create([
                'user_id'   => $request->user_id,
                'sale_id'   => $request->sale_id,
                'client_id' => $request->client_id,
                'date'      => now(),
                'amount'    => $request->amount,
            ]);

            $model = Sale::where('id', $request->sale_id)->first();

            if(!empty($model)){
                if ($model->status != 'eliminado') {
                    $total = $model->deposit + $request->amount;
                    $model->update(['deposit' => $total]);
                }
            }

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME3 . ' fue registrad' . self::GENDER2 . ' correctamente', new SaleDepositsHistoryResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME3 . ' no pudo ser guardad' . self::GENDER2, $th->getMessage(), 500);

        }

    }

    //? Eliminar pago
    public function destroyDeposit(string $id){

        try {

            $model = SaleDepositsHistory::where('id', $id)->first();

            if (!empty($model)){

                if ($model->status != 'eliminado') {

                    $modelSale = Sale::where('id', $model->sale_id)->first();

                    $total = $modelSale->deposit - $model->amount;

                    $model->status = 'eliminado';
                    $model->save();

                    $modelSale->deposit = $total;
                    $modelSale->save();

                    return SendResponse::message(true, 'destroy', self::NAME3 . ' fue eliminad' . self::GENDER2. ' correctamente', null, 200);

                }

                return SendResponse::message(false, 'destroy', self::NAME3 . ' ya se encuentra eliminad' . self::GENDER2, null, 400);

            }

            return SendResponse::message(false, 'destroy', self::NAME . ' ya se encuentra eliminad' . self::GENDER, null, 404);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'destroy', self::NAME . ' no pudo ser eliminad' . self::GENDER, $th->getMessage(), 500);

        }
        
    }

    //? Listar historial de pagos por venta
    public function getDepositsHistory(Request $request){

        $filters = $request->all();

        $data = SaleDepositsHistory::where('status', '!=', 'eliminado')
                                    ->where('sale_id', $filters['sale'])
                                    ->when(isset($filters['date']) && !empty($filters['date']), function ($q) use ($filters) {
                                        return $q->where('date', $filters['date']);
                                    })
                                    ->get();

        return new SalesDepositsHistoryCollection($data);

    }

    //? Guardar venta
    public function store(SaleRequest $request){

        try {

            DB::beginTransaction();

            $lastSale = Sale::latest('consecutive')->first();

            if ($lastSale) {
                $lastConsecutive = intval($lastSale->consecutive);
                $newConsecutive = str_pad($lastConsecutive + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $newConsecutive = '000001';
            }

            $model = Sale::create([
                'user_id'       => $request->user_id,
                'consecutive'   => $newConsecutive,
                'date'          => now(),
                'client_id'     => $request->client_id,
                'description'   => $request->description ?? "",
                'subtotal'      => $request->subtotal,
                'deposit'       => $request->deposit ?? 0,
                'consumption'   => $request->consumption ?? 0,
                'type'          => $request->type,
                'boleta_factura'=> $request->boleta_factura,
                'ruc'           => $request->boleta_factura == "factura" ? $request->ruc : "",
                'total'         => $request->total,
            ]);

            // Guardamos el primer depósito de la venta si es al crédito
            if($request->type == "credito"){
                SaleDepositsHistory::create([
                    'user_id'   => $request->user_id,
                    'sale_id'   => $model['id'],
                    'date'      => now(),
                    'amount'    => $request->deposit ?? 0,
                ]);
            }

            if ($request->has('details')) {

                /*
                    Validamos que haya stock suficiente para cada producto en los detalles
                */

                foreach ($request->details as $detail) {

                    $modelProduct = Product::where('id', $detail['product_id'])->with('unitMeasure')->where('status', '!=', 'eliminado')->first();

                    if (!$modelProduct) {
                        return SendResponse::message(false, 'store', 'Producto no encontrado', null, 404);
                    }

                    if($modelProduct['id_unit_measure'] == $detail['um']){

                        $stock = $modelProduct->stock;

                    } else {

                        $unitMeasureConvertion = UnitMeasureConvertion::where('status', '!=', 'eliminado')
                                                                        ->where('id_unit_measure', $modelProduct['id_unit_measure'])
                                                                        ->where('id_unit_measure_convert', $detail['um'])
                                                                        ->first();

                        if ($unitMeasureConvertion) {

                            $stock = $modelProduct->stock * $unitMeasureConvertion->amount;

                        } else {

                            $unitMeasureConvertion = UnitMeasureConvertion::where('status', '!=', 'eliminado')
                                                                            ->where('id_unit_measure', $detail['um'])
                                                                            ->where('id_unit_measure_convert', $modelProduct['id_unit_measure'])
                                                                            ->first();

                            if ($unitMeasureConvertion) {
                                $stock = $modelProduct->stock / $unitMeasureConvertion->amount;
                            } else {
                                return SendResponse::message(false, 'store', 'No se encontró conversión de unidades válida.', null, 404);
                            }

                        }

                    }

                    if($detail['amount'] > $stock){
                        return SendResponse::message(false, 'store', $modelProduct["name"] . ' no cuenta con stock suficiente, por favor elegir una cantidad menor', null, 200);
                    }

                }

                /*
                    Registramos los detalles y actualizamos el stock de los productos
                */

                foreach ($request->details as $detail) {

                    SaleDetails::create([
                        'sale_id'           => $model['id'],
                        'product_id'        => $detail['product_id'],
                        'amount'            => $detail['amount'],
                        'um'                => $detail['um'],
                        'name_unit_measure' => $detail['name_unit_measure'],
                        'price'             => $detail['price'],
                        'total'             => $detail['total'],
                    ]);

                    $product = Product::where('id', $detail['product_id'])->with('unitMeasure')->where('status', '!=', 'eliminado')->first();

                    $adjustedAmount = $detail['amount'];
                    if ($product['id_unit_measure'] != $detail['um']) {

                        $unitMeasureConvertion = UnitMeasureConvertion::where('status', '!=', 'eliminado')
                            ->where(function ($query) use ($product, $detail) {
                                $query->where('id_unit_measure', $product['id_unit_measure'])
                                    ->where('id_unit_measure_convert', $detail['um']);
                            })
                            ->orWhere(function ($query) use ($product, $detail) {
                                $query->where('id_unit_measure', $detail['um'])
                                    ->where('id_unit_measure_convert', $product['id_unit_measure']);
                            })
                            ->first();

                        if ($unitMeasureConvertion) {
                            if ($unitMeasureConvertion->id_unit_measure == $product['id_unit_measure']) {
                                $adjustedAmount = $detail['amount'] / $unitMeasureConvertion->amount;
                            } else {
                                $adjustedAmount = $detail['amount'] * $unitMeasureConvertion->amount;
                            }
                        } else {
                            return SendResponse::message(false, 'store', 'Conversión de unidades no encontrada.', null, 200);
                        }

                    }

                    $newStock = $product->stock - $adjustedAmount;
                    $stockDecrease = $product->stock - $newStock;

                    ProductStockHistory::create([
                        'product_id'        => $product->id,
                        'stock'             => $stockDecrease,
                        'date'              => now(),
                        'type'              => "venta_disminuye"
                    ]);

                    $product->update(['stock' => $newStock]);

                }

            }

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new SaleResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Ver venta
    public function show($id){

        try {

            $model = Sale::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new SaleResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar venta
    public function update(SaleRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = Sale::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->update([
                        'client_id'     => $request->client_id,
                        'description'   => $request->description ?? "",
                        'subtotal'      => $request->subtotal,
                        'deposit'       => $request->deposit ?? 0,
                        'consumption'   => $request->consumption ?? 0,
                        'type'          => $request->type,
                        'boleta_factura'=> $request->boleta_factura,
                        'ruc'           => $request->boleta_factura == "factura" ? $request->ruc : "",
                        'total'         => $request->total,
                    ]);

                    if ($request->has('details')) {

                        foreach ($request->details as $detail) {
                            if($detail['id'] == "-1"){

                                $modelProduct = Product::where('id', $detail['product_id'])->with('unitMeasure')->where('status', '!=', 'eliminado')->first();

                                if (!$modelProduct) {
                                    return SendResponse::message(false, 'store', 'Producto no encontrado', null, 404);
                                }

                                if($modelProduct['id_unit_measure'] == $detail['um']){

                                    $stock = $modelProduct->stock;

                                } else {

                                    $unitMeasureConvertion = UnitMeasureConvertion::where('status', '!=', 'eliminado')
                                                                                    ->where('id_unit_measure', $modelProduct['id_unit_measure'])
                                                                                    ->where('id_unit_measure_convert', $detail['um'])
                                                                                    ->first();

                                    if ($unitMeasureConvertion) {

                                        $stock = $modelProduct->stock * $unitMeasureConvertion->amount;

                                    } else {

                                        $unitMeasureConvertion = UnitMeasureConvertion::where('status', '!=', 'eliminado')
                                                                                        ->where('id_unit_measure', $detail['um'])
                                                                                        ->where('id_unit_measure_convert', $modelProduct['id_unit_measure'])
                                                                                        ->first();

                                        if ($unitMeasureConvertion) {
                                            $stock = $modelProduct->stock / $unitMeasureConvertion->amount;
                                        } else {
                                            return SendResponse::message(false, 'store', 'No se encontró conversión de unidades válida.', null, 404);
                                        }

                                    }

                                }

                                if($detail['amount'] > $stock){
                                    return SendResponse::message(false, 'store', $modelProduct["name"] . ' no cuenta con stock suficiente, por favor elegir una cantidad menor', null, 200);
                                }

                            }
                        }

                        foreach ($request->details as $detail) {
                            if($detail['id'] == "-1"){

                                SaleDetails::create([
                                    'sale_id'           => $model['id'],
                                    'product_id'        => $detail['product_id'],
                                    'amount'            => $detail['amount'],
                                    'um'                => $detail['um'],
                                    'name_unit_measure' => $detail['name_unit_measure'],
                                    'price'             => $detail['price'],
                                    'total'             => $detail['total'],
                                ]);

                                $product = Product::where('id', $detail['product_id'])->with('unitMeasure')->where('status', '!=', 'eliminado')->first();

                                $adjustedAmount = $detail['amount'];
                                if ($product['id_unit_measure'] != $detail['um']) {

                                    $unitMeasureConvertion = UnitMeasureConvertion::where('status', '!=', 'eliminado')
                                        ->where(function ($query) use ($product, $detail) {
                                            $query->where('id_unit_measure', $product['id_unit_measure'])
                                                ->where('id_unit_measure_convert', $detail['um']);
                                        })
                                        ->orWhere(function ($query) use ($product, $detail) {
                                            $query->where('id_unit_measure', $detail['um'])
                                                ->where('id_unit_measure_convert', $product['id_unit_measure']);
                                        })
                                        ->first();

                                    if ($unitMeasureConvertion) {
                                        if ($unitMeasureConvertion->id_unit_measure == $product['id_unit_measure']) {
                                            $adjustedAmount = $detail['amount'] / $unitMeasureConvertion->amount;
                                        } else {
                                            $adjustedAmount = $detail['amount'] * $unitMeasureConvertion->amount;
                                        }
                                    } else {
                                        return SendResponse::message(false, 'store', 'Conversión de unidades no encontrada.', null, 200);
                                    }

                                }

                                $newStock = $product->stock - $adjustedAmount;
                                $stockDecrease = $product->stock - $newStock;

                                ProductStockHistory::create([
                                    'product_id'        => $product->id,
                                    'stock'             => $stockDecrease,
                                    'date'              => now(),
                                    'type'              => "venta_disminuye"
                                ]);

                                $product->update(['stock' => $newStock]);

                            }
                        }

                    }

                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new SaleResource($model), 200);

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

    //? Eliminar venta
    public function destroy(string $id){

        try {

            $model = Sale::where('id', $id)->with('details')->first();

            if (!empty($model)){

                if ($model->status != 'eliminado') {

                    if ($model->details) {

                        /*
                            Devolvemos el stock de los productos
                        */

                        foreach ($model->details as $detail) {

                            $product = Product::where('id', $detail['product_id'])->with('unitMeasure')->where('status', '!=', 'eliminado')->first();

                            if (!empty($product)){

                                $adjustedAmount = $detail['amount'];
                                if ($product['id_unit_measure'] != $detail['um']) {

                                    $unitMeasureConvertion = UnitMeasureConvertion::where('status', '!=', 'eliminado')
                                        ->where(function ($query) use ($product, $detail) {
                                            $query->where('id_unit_measure', $product['id_unit_measure'])
                                                ->where('id_unit_measure_convert', $detail['um']);
                                        })
                                        ->orWhere(function ($query) use ($product, $detail) {
                                            $query->where('id_unit_measure', $detail['um'])
                                                ->where('id_unit_measure_convert', $product['id_unit_measure']);
                                        })
                                        ->first();

                                    if ($unitMeasureConvertion) {
                                        if ($unitMeasureConvertion->id_unit_measure == $product['id_unit_measure']) {
                                            $adjustedAmount = $detail['amount'] / $unitMeasureConvertion->amount;
                                        } else {
                                            $adjustedAmount = $detail['amount'] * $unitMeasureConvertion->amount;
                                        }
                                    } else {
                                        return SendResponse::message(false, 'store', 'Conversión de unidades no encontrada.', null, 200);
                                    }

                                }

                                $newStock = $product->stock + $adjustedAmount;

                                $stockIncrease = $newStock - $product->stock;

                                ProductStockHistory::create([
                                    'product_id'        => $product->id,
                                    'stock'             => $stockIncrease,
                                    'date'              => now(),
                                    'type'              => "venta_aumento"
                                ]);

                                $product->update(['stock' => $newStock]);

                            }

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

    //? Eliminar detalle de venta
    public function destroyDetails(string $id){

        try {

            $model = SaleDetails::where('id', $id)->first();

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

    public function excelSales(Request $request){

        $filters = $request->json()->all();

        $data = Sale::where('status', '!=', 'eliminado')
                        ->when(isset($filters['consecutive']) && !empty($filters['consecutive']), function ($q) use ($filters) {
                            return $q->where('consecutive', $filters['consecutive']);
                        })
                        ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                            return $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                        })
                        ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($q) use ($filters) {
                            return $q->where('date', $filters['date']);
                        })
                        ->when(isset($filters['client']) && !empty($filters['client']), function ($query) use ($filters) {
                            return $query->whereHas('client', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['client'] . '%');
                            });
                        })
                        ->when(isset($filters['user']) && !empty($filters['user']), function ($query) use ($filters) {
                            return $query->whereHas('user', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['user'] . '%');
                            });
                        })
                        ->get();

        $salesCollection = new SalesCollection($data);

        // Crear una nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Listado de Ventas');
        $sheet->mergeCells('A1:I1');

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
        $sheet->getStyle('A1:I1')->applyFromArray($titleStyle);
        $sheet->getRowDimension('1')->setRowHeight(30); // Ajustar la altura de la fila del título

        // Agregar los encabezados en la segunda fila
        $headers = ['Consecutivo', 'Fecha', 'Cliente', 'Usuario', 'Tipo', 'Descripción', 'Subtotal', 'Depositó', 'Total'];
        $sheet->fromArray($headers, null, 'A2');

        // Estilo para los encabezados de la tabla
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'], // Color de texto (blanco)
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'], // Color de fondo (azul)
            ],
        ];

        // Aplicar el estilo a los encabezados
        $sheet->getStyle('A2:I2')->applyFromArray($headerStyle);

        // Ajustar la altura de la fila de los encabezados
        $sheet->getRowDimension('2')->setRowHeight(20);
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(25);

        // Agregar los datos de las ventas a partir de la tercera fila
        $row = 3;
        foreach ($salesCollection as $sale) {
            $sheet->setCellValue('A' . $row, $sale->consecutive);
            $sheet->setCellValue('B' . $row, $sale->date);
            $sheet->setCellValue('C' . $row, $sale->client->name ?? '');
            $sheet->setCellValue('D' . $row, $sale->user->name ?? '');
            $sheet->setCellValue('E' . $row, ($sale->type == "contado" ? "Contado" : "Crédito"));
            $sheet->setCellValue('F' . $row, $sale->description?? '');
            $sheet->setCellValue('G' . $row, $sale->subtotal);
            $sheet->setCellValue('H' . $row, $sale->deposit);
            $sheet->setCellValue('I' . $row, $sale->total);
            $row++;
        }

        $sheet->setCellValue('F' . $row, 'Total');
        $sheet->setCellValue('G' . $row, '=SUM(G3:G' . ($row - 1) . ')'); // Suma de la columna Depositó
        $sheet->setCellValue('H' . $row, '=SUM(H3:H' . ($row - 1) . ')'); // Suma de la columna Consumo
        $sheet->setCellValue('I' . $row, '=SUM(I3:I' . ($row - 1) . ')'); // Suma de la columna Total

        $sheet->getStyle('F' . $row . ':I' . $row)->applyFromArray($headerStyle);


        $writer = new Xlsx($spreadsheet);

        $fileName = 'reporte_ventas.xlsx';

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

    public function excelSalesDetails(Request $request) {

        $filters = $request->all();
        $salesDetailsCollection = $this->getSalesPurchasesDetails($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Caja');
        $sheet->mergeCells('A1:J1');

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
        $sheet->getStyle('A1:J1')->applyFromArray($titleStyle);
        $sheet->getRowDimension('1')->setRowHeight(30);
    
        // Encabezados de la tabla
        $headers = ['#', 'Tipo', 'Número de Venta/Compra', 'Día de Creación', 'Cliente/Proveedor', 'Usuario', 'Producto', 'Cantidad', 'Precio', 'Total'];
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
        ];
        $sheet->getStyle('A2:J2')->applyFromArray($headerStyle);
        $sheet->getRowDimension('2')->setRowHeight(20);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->getColumnDimension('G')->setWidth(30);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(15);

        // Agregar los datos de las ventas y compras
        $row = 3;
        $totalSales = 0;
        $totalPurchases = 0;
        foreach ($salesDetailsCollection as $key => $detail) {

            $typeItem = $detail->typeItem;

            $product = $detail->product;

            $sale = $typeItem === "sale" ? $detail->sale : null;
            $purchase = $typeItem === "purchase" ? $detail->purchase : null;

            // Lógica para agregar datos
            $sheet->setCellValue('A' . $row, $key + 1);
            $sheet->setCellValue('B' . $row, ($typeItem == "sale" ? "Venta" : "Compra"));

            if ($typeItem === "sale") {
                $sheet->setCellValue('C' . $row, $sale->consecutive ?? 'Sin consecutivo');
                $sheet->setCellValue('D' . $row, $sale->date ?? 'Sin fecha');
                $sheet->setCellValue('E' . $row, $sale->client->name ?? 'Sin cliente');
                $sheet->setCellValue('F' . $row, $sale->user->name ?? 'Sin usuario');
            } else {
                $sheet->setCellValue('C' . $row, $purchase->consecutive ?? 'Sin consecutivo');
                $sheet->setCellValue('D' . $row, $purchase->date ?? 'Sin fecha');
                $sheet->setCellValue('E' . $row, $purchase->provider->name ?? 'Sin proveedor');
                $sheet->setCellValue('F' . $row, $purchase->user->name ?? 'Sin usuario');
            }

            $sheet->setCellValue('G' . $row, $product->name ?? 'Sin producto');
            // $sheet->setCellValue('H' . $row, $product->type ?? 'Sin tipo');
    
            // Establecer la cantidad y el precio
            $amount = $detail->amount;
            $price = $detail->price;
            $total = $amount * $price;
    
            // Sumar o restar al total según el tipo
            if ($typeItem === "sale") {
                $totalSales += $total;
            } else {
                $totalPurchases += $total; // Para compras, lo consideramos negativo
            }
    
            // Pintar la fila según el tipo
            $sheet->setCellValue('H' . $row, $amount);
            $sheet->setCellValue('I' . $row, $price);
            $sheet->setCellValue('J' . $row, $total); // Cambiar J a K
    
            if ($typeItem === "sale") {
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFCCFFCC'], // Verde claro
                    ]
                ]);
            } else {
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFCCCC'], // Rojo claro
                    ]
                ]);
            }
            $row++;
        }
    
        // Calcular las sumas totales
        $sheet->setCellValue('G' . $row, 'Total');
        $sheet->setCellValue('H' . $row, '=SUM(H3:H' . ($row - 1) . ')'); // Total de cantidades
        $sheet->setCellValue('I' . $row, '=SUM(I3:I' . ($row - 1) . ')'); // Total de precios
        $sheet->setCellValue('J' . $row, ($totalPurchases - $totalSales)); // Total general
    
        // Aplicar estilo a la fila de sumas
        $sumStyle = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'],
            ],
        ];
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($sumStyle);
    
        // Descargar el archivo Excel
        $writer = new Xlsx($spreadsheet);
        $fileName = 'reporte_ventas.xlsx';
    
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

    public function excelSale(Request $request){

        $filters = $request->json()->all();

        $saleId = $filters['id'];
        $sale = Sale::with(['client', 'details.product'])->findOrFail($saleId);

        // Crear un nuevo archivo de Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Estilo para el encabezado "Nutrivan"
        $nutrivanStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'], // Azul
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Encabezado
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'Pencaspampa');
        $sheet->getStyle('A1')->applyFromArray($nutrivanStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        // Información del usuario
        $sheet->setCellValue('B2', 'De: ' . $sale->user->name);
        $sheet->setCellValue('B3', 'Teléfono: ' . $sale->user->phone);
        $sheet->setCellValue('B4', 'Email: ' . $sale->user->email);

        $totalStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_BLACK],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];

        $sheet->setCellValue('E2', 'RUC: XXXXXXXX');
        $sheet->setCellValue('E3', $sale->boleta_factura == "boleta" ? 'BOLETA DE VENTA' : 'FACTURA');
        $sheet->setCellValue('E4', 'N°: ' . $sale->consecutive);

        $sheet->getStyle('E2')->applyFromArray($totalStyle);
        $sheet->getStyle('E3')->applyFromArray($totalStyle);
        $sheet->getStyle('E4')->applyFromArray($totalStyle);

        // Información del cliente
        $sheet->setCellValue('A6', 'Cliente:');
        $sheet->setCellValue('B6', $sale->client->name);
        $sheet->setCellValue('A7', 'Dirección:');
        $sheet->setCellValue('B7', $sale->client->address);
        
        $sheet->mergeCells('E6:E7');
        $sheet->setCellValue('E6', 'Fecha: ' . $sale->date);
        $sheet->getStyle('E6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E6')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        
        // Bordes para la información del cliente
        $sheet->getStyle('A6:B7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Encabezados de la tabla
        $sheet->setCellValue('A9', 'N°');
        $sheet->setCellValue('B9', 'PRODUCTO');
        $sheet->setCellValue('C9', 'CANTIDAD');
        $sheet->setCellValue('D9', 'P. UNITARIO');
        $sheet->setCellValue('E9', 'IMPORTE');
        
        // Estilo para el encabezado de la tabla
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0070C0'], // Azul
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
        
        $sheet->getStyle('A9:E9')->applyFromArray($headerStyle);
        
        // Insertar detalles de la venta
        $row = 10; // Iniciar en la fila 10
        foreach ($sale->details as $index => $detail) {
            $sheet->setCellValue('A' . $row, $index + 1); // Número de compra
            $sheet->setCellValue('B' . $row, $detail->product->name);
            $sheet->setCellValue('C' . $row, $detail->amount);
            $sheet->setCellValue('D' . $row, 'S/. ' . number_format($detail->price, 4));
            $sheet->setCellValue('E' . $row, 'S/. ' . number_format($detail->amount * $detail->price, 4));
        
            // Aplicar bordes a las filas de detalles
            $sheet->getStyle('A' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
            $row++;
        }
        
        // Total
        $sheet->setCellValue('D' . $row, 'Subtotal');
        $sheet->setCellValue('E' . $row, 'S/. ' . number_format($sale->subtotal, 4));
        $sheet->getStyle('D' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;

        if($sale->type == "credito"){
            $sheet->setCellValue('D' . $row, 'Depositó');
            $sheet->setCellValue('E' . $row, 'S/. ' . number_format($sale->deposit, 4));
            $sheet->getStyle('D' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }

        // $sheet->setCellValue('D' . $row, 'Consumo');
        // $sheet->setCellValue('E' . $row, 'S/. ' . number_format($sale->consumption, 2));
        // $sheet->getStyle('D' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        // $row++;

        $sheet->setCellValue('D' . $row, 'Total');
        $sheet->setCellValue('E' . $row, 'S/. ' . number_format($sale->total, 4));
        $sheet->getStyle('D' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Estilo para el total
        $totalStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_BLACK],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFF00'], // Amarillo
            ],
        ];
        
        $sheet->getStyle('D' . $row)->applyFromArray($totalStyle);
        $sheet->getStyle('E' . $row)->applyFromArray($totalStyle);

        // Ajustar el ancho de las columnas
        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Crear el archivo Excel
        $writer = new Xlsx($spreadsheet);
        $fileName = 'venta_' . $sale->consecutive . '.xlsx';

        // Configurar la respuesta para descargar el archivo
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);

    }

    public function pdfSale(Request $request) {

        $filters = $request->json()->all();
        $saleId = $filters['id'];
        $sale = Sale::with(['client', 'details.product', 'user'])->findOrFail($saleId);

        $data = [
            'sale'      => $sale,
            'user'      => $sale->user,
            'client'    => $sale->client,
            'details'   => $sale->details,
        ];

        $width = 80;
        $height = 210;

        $pdf = Pdf::loadView('pdf.sale', $data)->setPaper([0, 0, $width * 3.7795275591, $height * 3.7795275591], 'portrait')->setOption('isHtml5ParserEnabled', true);

        $fileName = 'venta_' . $sale->consecutive . '.pdf';

        return $pdf->download($fileName);

    }

    private function getSalesPurchasesDetails($filters){

        $dataSale = SaleDetails::where('status', '!=', 'eliminado')
                        ->selectRaw('*, \'sale\' as typeItem')
                        ->with("sale")
                        ->orderBy('created_at')
                        ->whereHas('sale', function ($query) {
                            $query->where('status', 'activo');
                        })
                        ->when(isset($filters['consecutive']) && !empty($filters['consecutive']), function ($query) use ($filters) {
                            return $query->whereHas('sale', function ($q) use ($filters) {
                                $q->where('consecutive', 'like', '%' . $filters['sale'] . '%');
                            });
                        })
                        ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($query) use ($filters) {
                            return $query->whereHas('sale', function ($q) use ($filters) {
                                $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                            });
                        })
                        ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($query) use ($filters) {
                            return $query->whereHas('sale', function ($q) use ($filters) {
                                $q->where('date', $filters['date']);
                            });
                        })
                        ->when(isset($filters['client']) && !empty($filters['client']), function ($query) use ($filters) {
                            return $query->whereHas('sale.client', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['client'] . '%');
                            });
                        })
                        ->when(isset($filters['user']) && !empty($filters['user']), function ($query) use ($filters) {
                            return $query->whereHas('sale.user', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['user'] . '%');
                            });
                        })
                        ->when(isset($filters['product']) && !empty($filters['product']), function ($query) use ($filters) {
                            return $query->whereHas('product', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['product'] . '%');
                            });
                        })
                        ->when(isset($filters['typeProduct']) && !empty($filters['typeProduct']) && ($filters['typeProduct'] != "ambas"), function ($query) use ($filters) {
                            return $query->whereHas('product', function ($q) use ($filters) {
                                $q->where('type', $filters['typeProduct']);
                            });
                        })
                        ->when(isset($filters['type']) && !empty($filters['type']) && ($filters['type'] != "ambas"), function ($query) use ($filters) {
                            return $query->whereHas('sale', function ($q) use ($filters) {
                                $q->where('type', $filters['type']);
                            });
                        })->get();

        $dataPurchase = PurchaseDetails::where('status', '!=', 'eliminado')
                        ->selectRaw('*, \'purchase\' as typeItem')
                        ->with("purchase")
                        ->orderBy('created_at')
                        ->whereHas('purchase', function ($query) {
                            $query->where('status', 'activo');
                        })
                        ->when(isset($filters['consecutive']) && !empty($filters['consecutive']), function ($query) use ($filters) {
                            return $query->whereHas('purchase', function ($q) use ($filters) {
                                $q->where('consecutive', 'like', '%' . $filters['purchase'] . '%');
                            });
                        })
                        ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($query) use ($filters) {
                            return $query->whereHas('purchase', function ($q) use ($filters) {
                                $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                            });
                        })
                        ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($query) use ($filters) {
                            return $query->whereHas('purchase', function ($q) use ($filters) {
                                $q->where('date', $filters['date']);
                            });
                        })
                        ->when(isset($filters['client']) && !empty($filters['client']), function ($query) use ($filters) {
                            return $query->whereHas('purchase.provider', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['client'] . '%');
                            });
                        })
                        ->when(isset($filters['user']) && !empty($filters['user']), function ($query) use ($filters) {
                            return $query->whereHas('purchase.user', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['user'] . '%');
                            });
                        })
                        ->when(isset($filters['product']) && !empty($filters['product']), function ($query) use ($filters) {
                            return $query->whereHas('product', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['product'] . '%');
                            });
                        })
                        ->when(isset($filters['typeProduct']) && !empty($filters['typeProduct']) && ($filters['typeProduct'] != "ambas"), function ($query) use ($filters) {
                            return $query->whereHas('product', function ($q) use ($filters) {
                                $q->where('type', $filters['typeProduct']);
                            });
                        })
                        ->when(isset($filters['type']) && !empty($filters['type']) && ($filters['type'] != "ambas"), function ($query) use ($filters) {
                            return $query->whereHas('sale', function ($q) use ($filters) {
                                $q->where('type', $filters['type']);
                            });
                        })->get();

        $data = $dataSale->concat($dataPurchase);

        return $data;

    }

}
