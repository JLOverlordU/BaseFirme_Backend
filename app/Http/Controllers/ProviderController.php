<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Administrable\ClientProvider;
use App\Http\Resources\Administrable\ClientsProviders\ClientProviderResource;
use App\Http\Resources\Administrable\ClientsProviders\ClientsProvidersCollection;
use App\Http\Requests\Administrable\ClientsProviders\ClientProviderRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class ProviderController extends Controller{

    const NAME      = 'El proveedor';
    const GENDER    = 'o';

    //? Listar proveedores
    public function index(Request $request){

        $filters = $request->all();

        $data = ClientProvider::where('status', '!=', 'eliminado')
                                ->when(isset($filters['document']) && !empty($filters['document']), function ($q) use ($filters) {
                                    return $q->where('document', 'like', '%' . $filters['document'] . '%');
                                })
                                ->when(isset($filters['name']) && !empty($filters['name']), function ($q) use ($filters) {
                                    return $q->where('name', 'like', '%' . $filters['name'] . '%');
                                })
                                ->when(isset($filters['email']) && !empty($filters['email']), function ($q) use ($filters) {
                                    return $q->where('email', 'like', '%' . $filters['email'] . '%');
                                })
                                ->when(isset($filters['type']) && !empty($filters['type']), function ($q) use ($filters) {
                                    if($filters['type'] == "credito"){
                                        return $q->where('type', '=', 'provider')->has('typePurchaseCredito');
                                    } else if($filters['type'] == "contado"){
                                        return $q->where('type', '=', 'provider')->has('typePurchaseContado');;
                                    } else {
                                        return $q->where('type', '=', 'provider');
                                    }
                                })
                                ->where('type', '=', 'provider')
                                ->with('lastDepositProvider')
                                ->get();

        return new ClientsProvidersCollection($data);

    }

    //? Guardar proveedor
    public function store(ClientProviderRequest $request){
        
        try {
            
            DB::beginTransaction();
            
            $model = ClientProvider::create([
                'type'        => "provider",
                'document'    => $request->document,
                'name'        => $request->name,
                'phone'       => $request->phone,
                'email'       => $request->email,
                'address'     => $request->address,
                'description' => $request->description ?? "",
            ]);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new ClientProviderResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Ver proveedor
    public function show($id){

        try {

            $model = ClientProvider::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new ClientProviderResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar proveedor
    public function update(ClientProviderRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = ClientProvider::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->update([
                        'type'        => "provider",
                        'document'    => $request->document,
                        'name'        => $request->name,
                        'phone'       => $request->phone,
                        'email'       => $request->email,
                        'address'     => $request->address,
                        'description' => $request->description ?? "",
                    ]);

                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new ClientProviderResource($model), 200);

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

    //? Eliminar proveedor
    public function destroy(string $id){

        try {

            $model = ClientProvider::where('id', $id)->first();

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
