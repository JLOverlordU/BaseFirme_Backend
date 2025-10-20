<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Maestras\UnitMeasure;

class ActiveUnitMeasure implements Rule{

    public function passes($attribute, $value){
        return UnitMeasure::where('id', $value)->where('status', 'activo')->exists();
    }

    public function message(){
        return 'El unidad de medida seleccionada se encuentra eliminada.';
    }

}
