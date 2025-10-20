<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Administrable\User;

class ActiveUser implements Rule{

    public function passes($attribute, $value){
        return User::where('id', $value)->where('status', 'activo')->exists();
    }

    public function message(){
        return 'El usuario se encuentra eliminado.';
    }

}
