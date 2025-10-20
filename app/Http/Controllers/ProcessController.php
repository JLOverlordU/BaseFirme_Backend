<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Maestras\Process;
use App\Http\Resources\Maestras\Processes\ProcessResource;
use App\Http\Resources\Maestras\Processes\ProcessesCollection;
use App\Http\Requests\Maestras\ProcessRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class ProcessController extends Controller{
    
    const NAME      = 'El proceso';
    const GENDER    = 'o';

    //? Listar procesos
    public function index(Request $request){

        $filters = $request->all();

        $processes = Process::where('status', '!=', 'eliminado')
                                    ->when(isset($filters['name']) && !empty($filters['name']), function ($q) use ($filters) {
                                        return $q->where('name', 'like', '%' . $filters['name'] . '%');
                                    })
                                    ->get();

        return new ProcessesCollection($processes);

    }

    //? Guardar proceso
    public function store(ProcessRequest $request){
        
        try {
            
            DB::beginTransaction();
            $inputs = $request->input();
            $model = Process::create($inputs);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new ProcessResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Ver proceso
    public function show($id){

        try {
        
            $model = Process::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new ProcessResource($model), 200);
   
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);

            }
            
        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar proceso
    public function update(ProcessRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = Process::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->update([
                        'name' => $request->name,
                    ]);
                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new ProcessResource($model), 200);

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

    //? Eliminar proceso
    public function destroy(string $id){

        try {
            
            $model = Process::where('id', $id)->first();

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
