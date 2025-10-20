<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Formulas\Formula;

class ActiveFormula implements Rule{

    public function passes($attribute, $value){
        return Formula::where('id', $value)->where('status', 'activo')->exists();
    }

    public function message(){
        return 'La fórmula seleccionada se encuentra eliminado.';
    }

}
