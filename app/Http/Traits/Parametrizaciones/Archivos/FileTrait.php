<?php

namespace App\Http\Traits\Parametrizaciones\Archivos;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\Parametrizaciones\Archivos\FileBaseClass;

trait FileTrait{

    
    function saveFile($file, String $typeAmbient, String $urlBase)
    {
        try {
            if (!is_null($file)) {
                $file_m = new FileBaseClass();
                return $file_m->putFileLocal($file, $typeAmbient, $urlBase);
            }
            return ['flag' => true, 'data' => ['url' => null]];
        } catch (\Throwable $th) {
            throw new Exception('(fn => saveFile, Error => ' . $th->getMessage() . ')');
        }
    }

}
