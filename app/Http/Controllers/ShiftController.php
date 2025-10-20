<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Maestras\Shift;
use App\Http\Resources\Maestras\Shifts\ShiftResource;
use App\Http\Resources\Maestras\Shifts\ShiftsCollection;
use App\Http\Requests\Maestras\ShiftRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class ShiftController extends Controller{
    
    const NAME      = 'El turno';
    const GENDER    = 'o';

    //? Listar procesos
    public function index(Request $request){

        $filters = $request->all();

        $presentations = Shift::where('status', '!=', 'eliminado')
                                ->when(isset($filters['name']) && !empty($filters['name']), function ($q) use ($filters) {
                                    return $q->where('name', 'like', '%' . $filters['name'] . '%');
                                })
                                ->get();

        return new ShiftsCollection($presentations);

    }

    //? Guardar turno
    public function store(ShiftRequest $request){
        
        try {
            
            DB::beginTransaction();
            $inputs = $request->input();
            $model = Shift::create($inputs);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new ShiftResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Ver turno
    public function show($id){

        try {
            
            $model = Shift::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new ShiftResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar turno
    public function update(ShiftRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = Shift::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->update([
                        'name' => $request->name,
                    ]);
                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new ShiftResource($model), 200);

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

    //? Eliminar turno
    public function destroy(string $id){

        try {
            
            $model = Shift::where('id', $id)->first();

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
