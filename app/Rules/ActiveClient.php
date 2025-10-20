<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Administrable\ClientProvider;

class ActiveClient implements Rule{

    public function passes($attribute, $value){
        return ClientProvider::where('id', $value)->where('type', 'client')->where('status', 'activo')->exists();
    }

    public function message(){
        return 'El cliente se encuentra eliminado.';
    }

}
