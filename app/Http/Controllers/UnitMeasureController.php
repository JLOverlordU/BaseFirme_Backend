<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Maestras\UnitMeasure;
use App\Models\Maestras\UnitMeasureConvertion;
use App\Http\Resources\Maestras\UnitsMeasure\UnitMeasureResource;
use App\Http\Resources\Maestras\UnitsMeasure\UnitsMeasureCollection;
use App\Http\Resources\Maestras\UnitsMeasure\UnitMeasureConvertionResource;
use App\Http\Resources\Maestras\UnitsMeasure\UnitsMeasureConvertionsCollection;
use App\Http\Requests\Maestras\UnitMeasureRequest;
use App\Http\Requests\Maestras\UnitMeasureConvertionRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class UnitMeasureController extends Controller{

    const NAME      = 'La unidad de medida';
    const NAME2     = 'La conversión';
    const GENDER    = 'a';

    //? Listar unidades de medida
    public function index(Request $request){

        $filters = $request->all();

        $unitsMeasure = UnitMeasure::where('status', '!=', 'eliminado')
                                    ->when(isset($filters['name']) && !empty($filters['name']), function ($q) use ($filters) {
                                        return $q->where('name', 'like', '%' . $filters['name'] . '%');
                                    })
                                    ->when(isset($filters['idNotUnit']) && !empty($filters['idNotUnit']), function ($q) use ($filters) {
                                        return $q->where('id', '!=', $filters['idNotUnit']);
                                    })
                                    ->get();

        return new UnitsMeasureCollection($unitsMeasure);

    }

    //? Guardar unidad de medida
    public function store(UnitMeasureRequest $request){

        try {

            DB::beginTransaction();
            $model = UnitMeasure::create([
                'slug' => Str::slug($request->name),
                'name' => $request->name,
            ]);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new UnitMeasureResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Ver unidad de medida
    public function show($id){

        try {

            $model = UnitMeasure::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new UnitMeasureResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar unidad de medida
    public function update(UnitMeasureRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = UnitMeasure::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->update([
                        'name' => $request->name,
                    ]);
                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new UnitMeasureResource($model), 200);

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

    //? Eliminar unidad de medida
    public function destroy(string $id){

        try {

            $model = UnitMeasure::where('id', $id)->first();

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

    //? Guardar conversión
    public function saveConvertion(UnitMeasureConvertionRequest $request){

        try {

            DB::beginTransaction();

            $model = UnitMeasureConvertion::where(function($query) use ($request) {
                $query->where('id_unit_measure', $request->id_unit_measure)
                    ->where('id_unit_measure_convert', $request->id_unit_measure_convert)
                    ->where('status', '!=', 'eliminado');
            })
            ->orWhere(function($query) use ($request) {
                $query->where('id_unit_measure', $request->id_unit_measure_convert)
                    ->where('id_unit_measure_convert', $request->id_unit_measure)
                    ->where('status', '!=', 'eliminado');
            })
            ->where('status', '!=', 'eliminado')
            ->first();

            if (!empty($model)) {
                return SendResponse::message(false, 'store', 'La conversión ya existe', null, 500);
            }

            $model = UnitMeasureConvertion::create([
                'id_unit_measure'           => $request->id_unit_measure,
                'amount'                    => $request->amount,
                'id_unit_measure_convert'   => $request->id_unit_measure_convert,
            ]);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME2 . ' fue registrad' . self::GENDER . ' correctamente', new UnitMeasureConvertionResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME2 . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Listar conversiones de unidades de medida
    public function getConvertionsByUnitMeasure(Request $request){

        $filters = $request->all();

        $unitsMeasure = UnitMeasureConvertion::where('status', '!=', 'eliminado')
                                    ->get();

        return new UnitsMeasureConvertionsCollection($unitsMeasure);

    }

    //? Eliminar unidad de medida
    public function destroyConvertion(string $id){

        try {

            $model = UnitMeasureConvertion::where('id', $id)->first();

            if (!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->status = 'eliminado';
                    $model->save();

                    return SendResponse::message(true, 'destroy', self::NAME2 . ' fue eliminad' . self::GENDER . ' correctamente', null, 200);

                }

                return SendResponse::message(false, 'destroy', self::NAME2 . ' ya se encuentra eliminad' . self::GENDER, null, 400);

            }

            return SendResponse::message(false, 'destroy', self::NAME2 . ' ya se encuentra eliminad' . self::GENDER, null, 404);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'destroy', self::NAME2 . ' no pudo ser eliminad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Obtener las conversiones posibles de la unidad de medida
    public function getUnitsMeasureConvert(Request $request){

        $filters = $request->all();

        $unitMeasureConversions = UnitMeasureConvertion::where('status', '!=', 'eliminado')
                                                        ->where(function ($query) use ($request) {
                                                            $query->where('id_unit_measure', $request->id_unit_measure)
                                                                ->orWhere('id_unit_measure_convert', $request->id_unit_measure);
                                                        })                                                
                                                        ->get();

        if ($unitMeasureConversions->isEmpty()) {
            $unitMeasure = UnitMeasure::find($request->id_unit_measure);
            return new UnitsMeasureCollection($unitMeasure ? [$unitMeasure] : []);
        }

        $unitMeasureIds = $unitMeasureConversions->flatMap(function ($conversion) use ($request) {
            return [$conversion->id_unit_measure, $conversion->id_unit_measure_convert];
        })->unique()->values()->toArray();

        $unitMeasures = UnitMeasure::whereIn('id', $unitMeasureIds)->get();

        return new UnitsMeasureCollection($unitMeasures);

    }

    //? Obtener la conversión de la unidad de medida
    public function getUnitMeasureConvert(Request $request){

        try {

            $id_unit_measure = $request->id_unit_measure;
            $id_unit_measure_convert = $request->id_unit_measure_convert;

            if($id_unit_measure == $id_unit_measure_convert){

                $multiplier = 1 * $request->amount;

                return SendResponse::message(true, 'show', self::NAME2 . ' es el mism' . self::GENDER, $multiplier, 200);

            } else {

                $modelConvertion = UnitMeasureConvertion::where('id_unit_measure', $id_unit_measure)->where('id_unit_measure_convert', $id_unit_measure_convert)->where('status', '!=', 'eliminado')->first();

                if (!empty($modelConvertion)) {

                    $multiplier = $modelConvertion->amount * $request->amount;

                    return SendResponse::message(true, 'show', self::NAME2 . ' se obtuvo correctamente', $multiplier, 200);

                } else {

                    $multiplier = 1 * $request->amount;

                    return SendResponse::message(false, 'show', self::NAME2 . ' no ha sido encontrad' . self::GENDER, $multiplier, 200);

                }

            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME2 . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

}
