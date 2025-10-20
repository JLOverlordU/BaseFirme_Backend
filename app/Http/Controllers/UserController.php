<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Models\Administrable\User;
use App\Http\Resources\Administrable\Users\Users\UserResource;
use App\Http\Resources\Administrable\Users\Users\UsersCollection;
use App\Http\Requests\Administrable\Users\UserRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class UserController extends Controller{

    const NAME      = 'El usuario';
    const GENDER    = 'o';

    //? Listar usuarios
    public function index(Request $request){

        $filters = $request->all();

        $data = User::where('status', '!=', 'eliminado')
                    ->when(isset($filters['username']) && !empty($filters['username']), function ($q) use ($filters) {
                        return $q->where('username', 'like', '%' . $filters['username'] . '%');
                    })
                    ->when(isset($filters['name']) && !empty($filters['name']), function ($q) use ($filters) {
                        return $q->where('name', 'like', '%' . $filters['name'] . '%');
                    })
                    ->when(isset($filters['email']) && !empty($filters['email']), function ($q) use ($filters) {
                        return $q->where('email', 'like', '%' . $filters['email'] . '%');
                    })
                    ->when(isset($filters['role']) && !empty($filters['role']), function ($query) use ($filters) {
                        return $query->whereHas('role', function ($q) use ($filters) {
                            $q->where('name', 'like', '%' . $filters['role'] . '%');
                        });
                    })
                    ->get();

        return new UsersCollection($data);

    }

    //? Guardar usuario
    public function store(UserRequest $request){
        
        try {
            
            DB::beginTransaction();
            $model = User::create([
                'username'          => $request->username,
                'name'              => $request->name,
                'email'             => $request->email,
                'phone'             => $request->phone,
                'password'          => Hash::make($request->password),
                'password_decrypted'=> $request->password,
                'role_id'           => $request->role_id,
            ]);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new UserResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Ver usuario
    public function show($id){

        try {

            $model = User::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new UserResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar usuario
    public function update(UserRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = User::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->update([
                        'username'          => $request->username,
                        'name'              => $request->name,
                        'email'             => $request->email,
                        'phone'             => $request->phone,
                        'password'          => Hash::make($request->password),
                        'password_decrypted'=> $request->password,
                        'role_id'           => $request->role_id,
                    ]);
                    
                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new UserResource($model), 200);

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

    //? Eliminar usuario
    public function destroy(string $id){

        try {

            $model = User::where('id', $id)->first();

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
