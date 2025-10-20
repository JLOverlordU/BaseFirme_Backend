<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Administrable\ClientProvider;

class ActiveProvider implements Rule{

    public function passes($attribute, $value){
        return ClientProvider::where('id', $value)->where('type', 'provider')->where('status', 'activo')->exists();
    }

    public function message(){
        return 'El proveedor se encuentra eliminado.';
    }

}
