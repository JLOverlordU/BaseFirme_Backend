<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Administrable\Role;
use App\Models\Administrable\Permission;
use App\Models\Administrable\RolePermission;
use App\Http\Resources\Administrable\Users\Roles\RoleResource;
use App\Http\Resources\Administrable\Users\Roles\RolesCollection;
use App\Http\Resources\Administrable\Users\Roles\PermissionCollection;
use App\Http\Requests\Administrable\Users\RoleRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class RoleController extends Controller{

    const NAME      = 'El rol';
    const GENDER    = 'o';

    //? Listar roles
    public function index(){

        $data = Role::where('status', '!=', 'eliminado')->get();

        return new RolesCollection($data);

    }

    public function getRolePermission(Request $request){

        $id = $request->id;

        $data = Role::where('id', $id)
                    ->with("permissions")
                    ->first();

        $permissions = RolePermission::where('role_id', $id)->where('status', 'activo')->with('permission')->get()->pluck('permission.slug');

        $data["array"] = $permissions;

        return new RoleResource($data);

    }

    public function getFunctions(Request $request){

        $filters = $request->all();

        $role = Role::with('permissions')->find($filters["role_id"]);

        $permissions = Permission::where('status', '!=', 'eliminado')->get();

        foreach ($permissions as $key => $permission) {

            $model = RolePermission::where('permission_id', $permission["id"])->where('role_id', $filters["role_id"])->where('status', "activo")->exists();

            $permission["checked"] = $model;

        }

        return new PermissionCollection($permissions);

    }

    //? Editar rol
    public function changeFunction(Request $request){
        
        try {
            
            DB::beginTransaction();

            $filters = $request->all();

            $model = RolePermission::where('permission_id', $filters["permission_id"])->where('role_id', $filters["role_id"])->first();

            if(!empty($model)){

                $status = ($model->status == "activo") ? "eliminado" : "activo";

                $model->update([
                    'status' => $status,
                ]);
                $model->refresh();

                DB::commit();

                return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER, $model, 200);

            } else {
                
                $model = RolePermission::create([
                    'role_id'       => $filters["role_id"],
                    'permission_id' => $filters["permission_id"],
                    'status'        => "activo",
                ]);

                DB::commit();
                
                return SendResponse::message(true, 'create', self::NAME . ' fue actualizad' . self::GENDER, $model, 200);

            }

            DB::rollback();
            return SendResponse::message(false, 'update', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'update', self::NAME . ' no pudo ser actualizad' . self::GENDER, $th->getMessage(), 500);

        }

    }

    //? Guardar rol
    public function store(RoleRequest $request){
        
        try {
            
            DB::beginTransaction();

            $model = Role::create([
                'slug' => Str::slug($request->name),
                'name' => $request->name,
            ]);

            DB::commit();

            return SendResponse::message(true, 'store', self::NAME . ' fue registrad' . self::GENDER . ' correctamente', new RoleResource($model), 200);

        } catch (\Throwable $th) {

            DB::rollback();
            return SendResponse::message(false, 'store', self::NAME . ' no pudo ser guardad' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Ver rol
    public function show($id){

        try {

            $model = Role::where('id', $id)->where('status', '!=', 'eliminado')->first();

            if (!empty($model)) {

                return SendResponse::message(true, 'show', self::NAME . ' se obtuvo correctamente', new RoleResource($model), 200);
            
            } else {

                return SendResponse::message(false, 'show', self::NAME . ' no ha sido encontrad' . self::GENDER, null, 404);
            
            }

        } catch (\Throwable $th) {

            return SendResponse::message(false, 'show', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
        
        }

    }

    //? Editar rol
    public function update(RoleRequest $request, string $id){
        
        try {

            DB::beginTransaction();
            $model = Role::where('id', $id)->first();

            if(!empty($model)){

                if ($model->status != 'eliminado') {

                    $model->update([
                        'name' => $request->name,
                    ]);
                    $model->refresh();

                    DB::commit();

                    return SendResponse::message(true, 'update', self::NAME . ' fue actualizad' . self::GENDER . ' correctamente', new RoleResource($model), 200);

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

    //? Eliminar rol
    public function destroy(string $id){

        try {

            $model = Role::where('id', $id)->first();

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
