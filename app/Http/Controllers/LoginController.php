<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;
use App\Models\Administrable\User;
use App\Http\Requests\Administrable\Users\UserRequest;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class LoginController extends Controller{

    const NAME      = 'El usuario';
    const GENDER    = 'o';

    // public function login(Request $request){

    //     try {

    //         $user = User::where('username', $request->username)->first();
            
    //         if (!$user) {
    //             return SendResponse::message(false, 'login', 'El usuario no existe, por favor ingrese un usuario correcto', null, 200);
    //         }

    //         $user = User::where('username', $request->username)->where('password_decrypted', $request->password)->first();
            
    //         if (!$user) {
    //             return SendResponse::message(false, 'login', 'Credenciales incorrectas, por favor verifique su contraseña', null, 200);
    //         }

    //         return SendResponse::message(true, 'login', self::NAME . ' ha iniciado sesión correctamente', $user, 200);

    //     } catch (\Throwable $th) {

    //         return SendResponse::message(false, 'login', self::NAME . ' no pudo iniciar sesión', $th->getMessage(), 500);

    //     }
    // }

    // public function login(Request $request){

    //     $request->validate([
    //         'username' => 'required',
    //         'password' => 'required',
    //     ]);

    //     $user = User::where('username', $request->username)->first();
            
    //     if (!$user) {
    //         return SendResponse::message(false, 'login', 'El usuario no existe, por favor ingrese un usuario correcto', null, 200);
    //     }

    //     $user = User::where('username', $request->username)->where('password_decrypted', $request->password)->first();
        
    //     if (!$user) {
    //         return SendResponse::message(false, 'login', 'Credenciales incorrectas, por favor verifique su contraseña', null, 200);
    //     }
    
    //     $credentials = $request->only('username', 'password');

    //     if (Auth::attempt($credentials)) {
    //         return SendResponse::message(true, 'login', self::NAME . ' ha iniciado sesión correctamente', $user, 200);
    //     }

    //     return SendResponse::message(false, 'login', 'Credenciales incorrectas', null, 200);

    // }

    public function login(Request $request){

        try {

            $request->validate([
                'username' => 'required',
                'password' => 'required',
            ]);
            
            $user = User::where('username', $request->username)->with("role")->first();
            
            if (!$user) {
                return SendResponse::message(false, 'login', 'El usuario no existe, por favor ingrese un usuario correcto', null, 200);
            }

            if (!Hash::check($request->password, $user->password)) {
                return SendResponse::message(false, 'login', 'Credenciales incorrectas, por favor verifique su contraseña', null, 200);
            }

            Auth::login($user);
            Session::put('user_data', $user);

            $userData = Session::get('user_data');

            return SendResponse::message(true, 'login', self::NAME . ' ha iniciado sesión correctamente', $userData, 200);

            // if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {

            //     Session::put('user_data', [
            //         'id' => $user->id,
            //         'role' => $user->role ? $user->role->name : null,
            //         'role_id' => $user->role_id,
            //         'username' => $user->username,
            //         'name' => $user->name,
            //         'email' => $user->email,
            //         'phone' => $user->phone,
            //     ]);

            //     $userData = Session::get('user_data');

            //     return SendResponse::message(true, 'login', self::NAME . ' ha iniciado sesión correctamente', $userData, 200);
            
            // }

            // return SendResponse::message(false, 'login', 'No se pudo iniciar sesión', null, 200);
        
        } catch (\Throwable $th) {

            return SendResponse::message(false, 'logout', self::NAME . ' no se pudo iniciar sesión', $th->getMessage(), 500);

        }

    }

    public function logout(Request $request){

        try {
        
            Auth::logout();

            return SendResponse::message(true, 'logout', self::NAME . ' ha cerrado sesión correctamente', null, 200);
        
        } catch (\Throwable $th) {

            return SendResponse::message(false, 'logout', self::NAME . ' no pudo cerrar sesión', $th->getMessage(), 500);

        }
        
    }

    public function getUser(Request $request){

        try {
            // dd(Session::all());
            // $userId = session('id');
            // $role_id = session('role_id');
            // $role = session('role');
            // $username = session('username');
            // $name = session('name');
            // $email = session('email');
            // $phone = session('phone');

            
            // $user = [
            //     'id' => $userId,
            //     'role_id' => $role_id,
            //     'role' => $role,
            //     'username' => $username,
            //     'name' => $name,
            //     'email' => $email,
            //     'phone' => $phone,
            // ];
            $user = Session::get('user_data');
            // $user = Auth::user();
            // $user = Auth::user();

            return SendResponse::message(true, 'login', self::NAME . ' se obtuvo correctamente', $user, 200);
    
        } catch (\Throwable $th) {

            return SendResponse::message(false, 'logout', self::NAME . ' no pudo ser obtenid' . self::GENDER, $th->getMessage(), 500);
    
        }

    }

}
