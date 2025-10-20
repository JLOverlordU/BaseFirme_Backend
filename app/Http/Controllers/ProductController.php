<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Maestras\UnitMeasure;
use App\Models\Products\Product;
use App\Models\Products\ProductStockHistory;
use App\Http\Resources\Products\ProductResource;
use App\Http\Resources\Products\ProductsCollection;
use App\Http\Resources\Products\ProductsStockHistoryCollection;
use App\Http\Requests\Products\ProductRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class ProductController extends Controller{

    const NAME      = 'El producto';
    const GENDER    = 'o';

    //? Listar productos
    public function index(){

        $data = Product::where('status', '!=', 'eliminado')->get();

        return new ProductsCollection($data);

    }
    
    //? Listar productos con filtros
    public function list(Request $request){

        $filters = $request->all();

        $data = Product::where('status', '!=', 'eliminado')
                        ->with("process", "unitMeasure")
                        ->when(isset($filters['cod_product']) && !empty($filters['cod_product']), function ($q) use ($filters) {
                            return $q->where('cod_product', $filters['cod_product']);
                        })
                        ->when(isset($filters['name']) && !empty($filters['name']), function ($q) use ($filters) {
                            return $q->where('name', 'like', '%' . $filters['name'] . '%');
                        })
                        ->when(isset($filters['process']) && !empty($filters['process']), function ($query) use ($filters) {
                            return $query->whereHas('process', function ($q) use ($filters) {
                                $q->where('name', 'like', '%' . $filters['process'] . '%');
                            });
                        })
                        ->when(isset($filters['type']) && !empty($filters['type']) && ($filters['type'] != "ambas"), function ($q) use ($filters) {
                            return $q->where('type', $filters['type']);
                        })
                        ->when(isset($filters['price']) && !empty($filters['price']), function ($q) use ($filters) {
                            return $q->where('price', $filters['price']);
                        })
                        ->when(isset($filters['stock']) && !empty($filters['stock']), function ($q) use ($filters) {
                            return $q->where('stock', $filters['stock']);
                        })
                        ->when(isset($filters['unit_measure']) && !empty($filters['unit_measure']), function ($q) use ($filters) {
                            return $q->where('id_unit_measure', $filters['unit_measure']);
                        })
                        ->when(isset($filters['ids_products']) && !empty($filters['ids_products']), function ($q) use ($filters) {
                            return $q->whereNotIn('id', $filters['ids_products']);
                        })
                        ->get();

        return new ProductsCollection($data);

    }

    //? Guardar producto
    public function store(ProductRequest $request){

        try {

            DB::beginTransaction();

            //? Verificamos que la unidad es kg
            $modelUnitMeasure = UnitMeasure::where('id', $request->id_unit_measure)->where('status', '!=', 'eliminado')->first();
            $stock = 0;

            $model = Product::create([
                'cod_product'       => $request->cod_product,
                'name'              => $request->name,
                'id_process'        => null,
                'id_presentation'   => null,
                'id_unit_measure'   => $request->id_unit_measure,
                'price'             => $request->price ?? 0,
                'price_purchase'    => $request->price_purchase ?? 0,
                'stock'             => $request->stock ?? 0,
                'minimum_quantity'  => $request->minimum_quantity ?? 0,
            ]);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new ProductResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Transferir Data
    public function transfer(Request $request){

        try {

            DB::beginTransaction();

            $product            = Product::where('id', $request->product1)->first();
            $newStock           = $product->stock - $request->amount;
            $newConvertedStock  = $product->equivalent != 0 ? number_format($newStock * $product->equivalent, 4, '.', '') : 0;

            //? Registrar historial
            ProductStockHistory::create([
                'product_id'        => $request->product1,
                'stock'             => $request->amount,
                'converted_stock'   => ($product->equivalent != 0 ? number_format($request->amount * $product->equivalent, 4, '.', '') : 0),
                'date'              => now(),
                'type'              => "transferencia_disminuye"
            ]);

            $product->update([
                'stock'             => $newStock,
                'converted_stock'   => $newConvertedStock,
            ]);

            $product2             = Product::where('id', $request->product2)->first();
            $newStock2            = $product2->stock + $request->amount;
            $newConvertedStock2   = $product2->equivalent != 0 ? number_format($newStock2 * $product->equivalent, 4, '.', '') : 0;

            //? Registrar historial
            ProductStockHistory::create([
                'product_id'        => $request->product2,
                'stock'             => $request->amount,
                'converted_stock'   => ($product2->equivalent != 0 ? number_format($request->amount * $product->equivalent, 4, '.', '') : 0),
                'date'              => now(),
                'type'              => "transferencia_aumento"
            ]);

            $product2->update([
                'stock'             => $newStock2,
                'converted_stock'   => $newConvertedStock2,
            ]);

            DB::commit();

            return SendResponse::message(true, 'transfer', 'La tranferencia fue registrada correctamente', null, 200);

        } catch (\Throwable$th) {

            DB::rollback();
            return SendResponse::message(false, 'transfer', 'La tranferencia no pudo ser guardada', $th->getMessage(), 500);

        }

    }

    //? Agregar Stock
    public function stock(Request $request){

        try {

            DB::beginTransaction();

            $product = Product::where('id', $request->id)->first();

            $modelUnitMeasure = UnitMeasure::where('id', $product->id_unit_measure)->where('status', '!=', 'eliminado')->first();

            //? Registrar historial
            ProductStockHistory::create([
                'product_id'        => $request->id,
                'stock'             => $request->stock,
                'date'              => now(),
                'type'              => "stock"
            ]);

            $product->update([
                'stock' => ($product->stock + $request->stock),
            ]);

            DB::commit();

            return SendResponse::message(true, 'stock', 'El stock fue agregado correctamente', null, 200);

        } catch (\Throwable$th) {

            DB::rollback();
            return SendResponse::message(false, 'stock', 'El stock no pudo ser agregado', $th->getMessage(), 500);

        }

    }

    //? Historial del stock del producto
    public function getProductStockHistory(Request $request){

        $filters = $request->all();

        $data = ProductStockHistory::where('status', '!=', 'eliminado')
                        ->where('product_id', $request->product_id)
                        ->get();

        return new ProductsStockHistoryCollection($data);

    }

    //? Ver producto
    public function show($id){ 

        try {

            $model = Product::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new ProductResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar producto
    public function update(ProductRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = Product::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    //? Verificamos que la unidad es kg
                    $modelUnitMeasure = UnitMeasure::where('id', $request->id_unit_measure)->where('status', '!=', 'eliminado')->first();

                    if($modelUnitMeasure->slug == "kg"){
                        $converted_price            = number_format($request->price * $request->equivalent, 4, '.', '');
                        $converted_price_purchase   = number_format($request->price_purchase * $request->equivalent, 4, '.', '');
                        $stock                      = $request->converted_stock;
                        $converted_stock            = $request->stock;
                    } else {
                        $converted_price            = number_format($request->price / $request->equivalent, 4, '.', '');
                        $converted_price_purchase   = number_format($request->price_purchase / $request->equivalent, 4, '.', '');
                        $stock                      = $request->stock;
                        $converted_stock            = $request->converted_stock;
                    }

                    $data = [
                        'cod_product'       => $request->cod_product,
                        'name'              => $request->name,
                        'id_process'        => null,
                        'id_presentation'   => null, //$request->id_presentation,
                        'id_unit_measure'   => $request->id_unit_measure,
                        'price'             => $request->price ?? 0,
                        'converted_price'   => $converted_price ?? 0,
                        'price_purchase'    => $request->price_purchase ?? 0,
                        'converted_price_purchase'  => $converted_price_purchase ?? 0,
                        'equivalent'        => $request->equivalent ?? 0,
                        'minimum_quantity'  => $request->minimum_quantity ?? 0,
                    ];

                    if($request->slug == "admin"){
                        $data["stock"] = $stock ?? 0;
                        $data["converted_stock"] = $converted_stock ?? 0;
                    }

                    $model->update($data);

                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new ProductResource($model), 200);

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

    //? Eliminar producto
    public function destroy(string $id){

        try {

            $model = Product::where('id', $id)->first();

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

}
