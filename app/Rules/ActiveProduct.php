<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Products\Product;

class ActiveProduct implements Rule{

    public function passes($attribute, $value){
        return Product::where('id', $value)->where('status', 'activo')->exists();
    }

    public function message(){
        return 'El producto seleccionado se encuentra eliminado.';
    }

}
