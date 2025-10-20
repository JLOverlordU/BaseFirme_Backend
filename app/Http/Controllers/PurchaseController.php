<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Str;

use App\Models\Purchases\Purchase;
use App\Models\Purchases\PurchaseDetails;
use App\Models\Purchases\PurchaseDepositsHistory;
use App\Models\Products\Product;
use App\Models\Products\ProductStockHistory;
use App\Models\Maestras\UnitMeasureConvertion;
use App\Http\Resources\Purchases\PurchaseResource;
use App\Http\Resources\Purchases\PurchaseDepositsHistoryResource;
use App\Http\Resources\Purchases\PurchasesCollection;
use App\Http\Resources\Purchases\PurchasesDepositsHistoryCollection;
use App\Http\Requests\Purchases\PurchaseRequest;
use App\Http\Requests\Purchases\PurchaseDepositRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Symfony\Component\HttpFoundation\Response;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller{

    const NAME      = 'La compra';
    const GENDER    = 'a';
    const NAME2     = 'El detalle';
    const GENDER2   = 'o';
    const NAME3     = 'El deposito';

    //? Listar compras
    public function index(Request $request){

        $filters = $request->all();

        $data = Purchase::where('status', '!=', 'eliminado')
                        ->when(isset($filters['consecutive']) && !empty($filters['consecutive']), function ($q) use ($filters) {
                            return $q->where('consecutive', $filters['consecutive']);
                        })
                        ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                            return $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                        })
                        ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($q) use ($filters) {
                            return $q->where('date', $filters['date']);
                        })
                        ->when(isset($filters['provider']) && !empty($filters['provider']), function ($query) use ($filters) {
                            return $query->whereHas('provider', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['provider'] . '%');
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

        return new PurchasesCollection($data);

    }

    //? Listar compras por proveedor
    public function getProviderByPurchases(Request $request){

        $filters = $request->all();

        $data = Purchase::where('status', '!=', 'eliminado')
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
                        ->when(isset($filters['provider']) && !empty($filters['provider']), function ($query) use ($filters) {
                            return $query->whereHas('provider', function ($q) use ($filters) {
                                $q->where('id', $filters['provider']);
                            });
                        })
                        ->get();

        return new PurchasesCollection($data);

    }

    //? Guardar historial de pago
    public function saveDepositsHistory(PurchaseDepositRequest $request){

        try {

            DB::beginTransaction();

            $model = PurchaseDepositsHistory::create([
                'user_id'       => $request->user_id,
                'purchase_id'   => $request->purchase_id,
                'provider_id'   => $request->provider_id,
                'date'          => now(),
                'amount'        => $request->amount,
            ]);

            $model = Purchase::where('id', $request->purchase_id)->first();

            if(!empty($model)){
                if ($model->status != 'eliminado') {
                    $total = $model->deposit + $request->amount;
                    $model->update(['deposit' => $total]);
                }
            }

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME3 . ' fue registrad' . self::GENDER2 . ' correctamente', new PurchaseDepositsHistoryResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME3 . ' no pudo ser guardad' . self::GENDER2, $th->getMessage(), 500);

        }

    }

    //? Eliminar pago
    public function destroyDeposit(string $id){

        try {

            $model = PurchaseDepositsHistory::where('id', $id)->first();

            if (!empty($model)){

                if ($model->status != 'eliminado') {

                    $modelSale = Purchase::where('id', $model->purchase_id)->first();

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

        $data = PurchaseDepositsHistory::where('status', '!=', 'eliminado')
                                    ->where('purchase_id', $filters['purchase'])
                                    ->when(isset($filters['date']) && !empty($filters['date']), function ($q) use ($filters) {
                                        return $q->where('date', $filters['date']);
                                    })
                                    ->get();

        return new PurchasesDepositsHistoryCollection($data);

    }

    //? Guardar compra
    public function store(PurchaseRequest $request){
        
        try {
            
            DB::beginTransaction();

            $lastSale = Purchase::latest('consecutive')->first();
            
            if ($lastSale) {
                $lastConsecutive = intval($lastSale->consecutive);
                $newConsecutive = str_pad($lastConsecutive + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $newConsecutive = '000001';
            }

            $model = Purchase::create([
                'user_id'       => $request->user_id,
                'consecutive'   => $newConsecutive,
                'date'          => now(),
                'provider_id'   => $request->provider_id,
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
                PurchaseDepositsHistory::create([
                    'user_id'       => $request->user_id,
                    'purchase_id'   => $model['id'],
                    'date'          => now(),
                    'amount'        => $request->deposit ?? 0,
                ]);
            }

            if ($request->has('details')) {

                /*
                    Registramos los detalles y actualizamos el stock de los productos
                */

                foreach ($request->details as $detail) {

                    PurchaseDetails::create([
                        'purchase_id'       => $model['id'],
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

                    $newStock = $product->stock + $adjustedAmount;
                    $stockIncrease = $newStock - $product->stock;

                    ProductStockHistory::create([
                        'product_id'        => $product->id,
                        'stock'             => $stockIncrease,
                        'date'              => now(),
                        'type'              => "compra_aumento"
                    ]);

                    $product->update(['stock' => $newStock]);

                }

            }

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new PurchaseResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Ver compra
    public function show($id){

        try {

            $model = Purchase::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new PurchaseResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar compra
    public function update(PurchaseRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = Purchase::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->update([
                        'provider_id'   => $request->provider_id,
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

                        /*
                            Actualizamos los detalles y actualizamos el stock de los productos
                        */

                        foreach ($request->details as $detail) {
                            if($detail['id'] == "-1"){

                                PurchaseDetails::create([
                                    'purchase_id'       => $model['id'],
                                    'product_id'        => $detail['product_id'],
                                    'amount'            => $detail['amount'],
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

                                $newStock = $product->stock + $adjustedAmount;
                                $stockIncrease = $newStock - $product->stock;

                                ProductStockHistory::create([
                                    'product_id'        => $product->id,
                                    'stock'             => $stockIncrease,
                                    'date'              => now(),
                                    'type'              => "compra_aumento"
                                ]);

                                $product->update(['stock' => $newStock]);

                            }
                        }

                    }

                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new PurchaseResource($model), 200);

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

    //? Eliminar compra
    public function destroy(string $id){

        try {

            $model = Purchase::where('id', $id)->with('details')->first();

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

                                $newStock = $product->stock - $adjustedAmount;
                                $stockDecrease = $product->stock - $newStock;

                                ProductStockHistory::create([
                                    'product_id'        => $product->id,
                                    'stock'             => $stockDecrease,
                                    'date'              => now(),
                                    'type'              => "compra_disminuye"
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

    //? Eliminar detalle de compra
    public function destroyDetails(string $id){

        try {

            $model = PurchaseDetails::where('id', $id)->first();

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

    public function excelPurchases(Request $request){

        $filters = $request->json()->all();

        $data = Purchase::where('status', '!=', 'eliminado')
                        ->when(isset($filters['consecutive']) && !empty($filters['consecutive']), function ($q) use ($filters) {
                            return $q->where('consecutive', $filters['consecutive']);
                        })
                        ->when(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date']), function ($q) use ($filters) {
                            return $q->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
                        })
                        ->when(!(isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) && isset($filters['date']) && !empty($filters['date']), function ($q) use ($filters) {
                            return $q->where('date', $filters['date']);
                        })
                        ->when(isset($filters['provider']) && !empty($filters['provider']), function ($query) use ($filters) {
                            return $query->whereHas('provider', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['provider'] . '%');
                            });
                        })
                        ->when(isset($filters['user']) && !empty($filters['user']), function ($query) use ($filters) {
                            return $query->whereHas('user', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['user'] . '%');
                            });
                        })
                        ->get();

        $purchasesCollection = new PurchasesCollection($data);

        // Crear una nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Listado de Compras');
        $sheet->mergeCells('A1:J1');

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
        $sheet->getStyle('A1:J1')->applyFromArray($titleStyle);
        $sheet->getRowDimension('1')->setRowHeight(30); // Ajustar la altura de la fila del título

        // Agregar los encabezados en la segunda fila
        $headers = ['Consecutivo', 'Fecha', 'Proveedor', 'Usuario', 'Tipo', 'Descripción', 'Subtotal', 'Depositó', 'Consumo', 'Total'];
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
        $sheet->getStyle('A2:J2')->applyFromArray($headerStyle);

        // Ajustar la altura de la fila de los encabezados
        $sheet->getRowDimension('2')->setRowHeight(20);
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(25);

        // Agregar los datos de las compras a partir de la tercera fila
        $row = 3;
        foreach ($purchasesCollection as $purchase) {
            $sheet->setCellValue('A' . $row, $purchase->consecutive);
            $sheet->setCellValue('B' . $row, $purchase->date);
            $sheet->setCellValue('C' . $row, $purchase->provider->name ?? '');
            $sheet->setCellValue('D' . $row, $purchase->user->name ?? '');
            $sheet->setCellValue('E' . $row, ($purchase->type == "contado" ? "Contado" : "Crédito"));
            $sheet->setCellValue('F' . $row, $purchase->description ?? '');
            $sheet->setCellValue('G' . $row, $purchase->subtotal);
            $sheet->setCellValue('H' . $row, $purchase->deposit);
            $sheet->setCellValue('I' . $row, $purchase->consumption);
            $sheet->setCellValue('J' . $row, $purchase->total);
            $row++;
        }

        $sheet->setCellValue('F' . $row, 'Total');
        $sheet->setCellValue('G' . $row, '=SUM(G3:G' . ($row - 1) . ')'); // Suma de la columna Subtotal
        $sheet->setCellValue('H' . $row, '=SUM(H3:H' . ($row - 1) . ')'); // Suma de la columna Depositó
        $sheet->setCellValue('I' . $row, '=SUM(I3:I' . ($row - 1) . ')'); // Suma de la columna Consumo
        $sheet->setCellValue('J' . $row, '=SUM(J3:J' . ($row - 1) . ')'); // Suma de la columna Total

        $sheet->getStyle('F' . $row . ':J' . $row)->applyFromArray($headerStyle);


        $writer = new Xlsx($spreadsheet);

        $fileName = 'reporte_compras.xlsx';

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

    public function excelPurchase(Request $request){

        $filters = $request->json()->all();

        $purchaseId = $filters['id'];
        $purchase = Purchase::with(['provider', 'details.product'])->findOrFail($purchaseId);

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
        $sheet->setCellValue('B2', 'De: ' . $purchase->user->name);
        $sheet->setCellValue('B3', 'Teléfono: ' . $purchase->user->phone);
        $sheet->setCellValue('B4', 'Email: ' . $purchase->user->email);

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
        $sheet->setCellValue('E3', $purchase->boleta_factura == "boleta" ? 'BOLETA DE COMPRA' : 'FACTURA');
        $sheet->setCellValue('E4', 'N°: ' . $purchase->consecutive);

        $sheet->getStyle('E2')->applyFromArray($totalStyle);
        $sheet->getStyle('E3')->applyFromArray($totalStyle);
        $sheet->getStyle('E4')->applyFromArray($totalStyle);

        // Información del cliente
        $sheet->setCellValue('A6', 'Proveedor:');
        $sheet->setCellValue('B6', $purchase->provider->name);
        $sheet->setCellValue('A7', 'Dirección:');
        $sheet->setCellValue('B7', $purchase->provider->address);

        $sheet->mergeCells('E6:E7');
        $sheet->setCellValue('E6', 'Fecha: ' . $purchase->date);
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

        // Insertar detalles de la compra
        $row = 10; // Iniciar en la fila 10
        foreach ($purchase->details as $index => $detail) {
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
        $sheet->setCellValue('E' . $row, 'S/. ' . number_format($purchase->subtotal, 4));
        $sheet->getStyle('D' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;

        if($purchase->type == "credito"){
            $sheet->setCellValue('D' . $row, 'Depositó');
            $sheet->setCellValue('E' . $row, 'S/. ' . number_format($purchase->deposit, 4));
            $sheet->getStyle('D' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }

        // $sheet->setCellValue('D' . $row, 'Consumo');
        // $sheet->setCellValue('E' . $row, 'S/. ' . number_format($purchase->consumption, 2));
        // $sheet->getStyle('D' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        // $row++;

        $sheet->setCellValue('D' . $row, 'Total');
        $sheet->setCellValue('E' . $row, 'S/. ' . number_format($purchase->total, 4));
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
        $fileName = 'compra_' . $purchase->consecutive . '.xlsx';

        // Configurar la respuesta para descargar el archivo
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);

    }

    public function pdfPurchase(Request $request) {

        $filters = $request->json()->all();
        $providerId = $filters['id'];
        $purchase = Purchase::with(['provider', 'details.product', 'user'])->findOrFail($providerId);

        $data = [
            'purchase'  => $purchase,
            'user'      => $purchase->user,
            'provider'  => $purchase->provider,
            'details'   => $purchase->details,
        ];

        $width = 80;
        $height = 210;

        $pdf = Pdf::loadView('pdf.purchase', $data)->setPaper([0, 0, $width * 3.7795275591, $height * 3.7795275591], 'portrait')->setOption('isHtml5ParserEnabled', true);

        $fileName = 'compra_' . $purchase->consecutive . '.pdf';

        // Retornar el PDF como respuesta
        return $pdf->download($fileName);

    }

}
