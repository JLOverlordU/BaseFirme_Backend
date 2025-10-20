<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Maestras\Machine;

class ActiveMachine implements Rule{

    public function passes($attribute, $value){
        return Machine::where('id', $value)->where('status', 'activo')->exists();
    }

    public function message(){
        return 'El máquina seleccionada se encuentra eliminada.';
    }

}
