<?php

namespace App\Http\Traits\Parametrizaciones\Respuestas;

use App\Models\Logger;
use App\Utils\Env\EnvHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait SendResponse{

    /**
	 * Método que setea una respuesta estandar
     * @param String    $tipo      - Indica el tipo de mensaje puede ser error, success o permission
     * @param String    $function  - Indica la funcion desde la que se envia el mensaje
	 * @param           $message   - Mensaje, puede ser un string o un array.
     * @param String    $codigo    - Codigo Http.
     */
    static function jsonMessage(String $tipo, String $function, $message, String $codigo){
        return response()->json([
            "message"                                           => 'Mensaje desde la función '.$function,
            ($tipo == 'success')?'success':'errors'             => [
                ($tipo == 'permission')?'permission':'server'   => (is_array($message)?$message:[$message])
            ]
        ],$codigo);
    }

    static function message($flag, String $function, String $message, $data, String $code){
        
        return response()->json([
            "flag"      => $flag,
            "function"  => $function,
            "message"   => $message,
            "data"      => $data
        ],$code);

    }

    /**
	 * Método que setea una respuesta estandar, tambien puede registrar un log con errores
	 * @param           $th         - Excepcion generada en el try catch de origen - No es obligatorio.
     * @param String    $function   - Indica la funcion en la que se presento el error
	 * @param String    $message    - Descripcion general del error.
     * @param String    $codigo     - Codigo Http.
     */
    static function jsonError($th, String $function ,String $message, String $codigo = '500'){
        $errors = '';
        $errorId = uniqid();

        Log::info("ID: $errorId Función: $function Error: ".$th);

        self::setExceptioBody($th, $message, $errors, $function, $codigo, $errorId);

        // Temporal para debug
        Logger::create(['exception' => $th, 'error_id' => $errorId]);

        return response()->json([
            "message"  => $message,
            "errors"   => $errors,
            'error_id' => $errorId
        ],$codigo);
    }

    static function setExceptioBody($th, &$message, &$errors, $function, &$codigo, $uniq_cod){
        if($th instanceof ValidationException){
            $message = $th->getMessage();
            $errors = $th->errors();
            $codigo = $th->status;
        }else{
            $message = $message;
            $errors = [
                "server" => ['Funcion => '.$function.', Error => '. (EnvHelper::get('MSG_DEBUG') == 'true' ? $th->getMessage() : "Error interno con código $uniq_cod por favor comunicarse con servicio técnico.")]
            ];
        }
    }

    static function storeResponse($resource, Int $code = 201){
        return response()->json($resource,$code);
    }

    static function updateResponse($resource, Int $code = 200){
        return response()->json($resource,$code);
    }

    static function indexResponse($resource){
        return $resource;
    }

    static function showResponse($resource){
        return $resource;
    }

    static function destroyResponse(String $message = 'Registro Eliminado', Int $code = 202){
        return self::jsonMessage('success', 'destroy', $message, $code);
    }
}
