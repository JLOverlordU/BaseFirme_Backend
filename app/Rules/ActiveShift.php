<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Maestras\Shift;

class ActiveShift implements Rule{

    public function passes($attribute, $value){
        return Shift::where('id', $value)->where('status', 'activo')->exists();
    }

    public function message(){
        return 'El turno seleccionado se encuentra eliminado.';
    }

}
