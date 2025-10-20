<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Maestras\Presentation;
use App\Http\Resources\Maestras\Presentations\PresentationResource;
use App\Http\Resources\Maestras\Presentations\PresentationsCollection;
use App\Http\Requests\Maestras\PresentationRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class PresentationController extends Controller{
    
    const NAME      = 'La presentación';
    const GENDER    = 'a';

    //? Listar presentaciones
    public function index(Request $request){

        $filters = $request->all();

        $presentations = Presentation::where('status', '!=', 'eliminado')
                                        ->when(isset($filters['name']) && !empty($filters['name']), function ($q) use ($filters) {
                                            return $q->where('name', 'like', '%' . $filters['name'] . '%');
                                        })
                                        ->get();

        return new PresentationsCollection($presentations);

    }

    //? Guardar presentación
    public function store(PresentationRequest $request){
        
        try {
            
            DB::beginTransaction();
            $inputs = $request->input();
            $model = Presentation::create($inputs);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new PresentationResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Ver presentación
    public function show($id){

        try {

            $model = Presentation::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new PresentationResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }
        
    }

    //? Editar presentación
    public function update(PresentationRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = Presentation::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->update([
                        'name' => $request->name,
                    ]);
                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new PresentationResource($model), 200);

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

    //? Eliminar presentación
    public function destroy(string $id){

        try {

            $model = Presentation::where('id', $id)->first();

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
