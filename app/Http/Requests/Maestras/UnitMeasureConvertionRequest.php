<?php

namespace App\Http\Requests\Maestras;

use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UnitMeasureConvertionRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){

        return [
            "id_unit_measure"           => "required|integer|exists:units_measure,id",
            'amount'                    => 'required|numeric',
            "id_unit_measure_convert"   => "required|integer|exists:units_measure,id",
        ];
    }

    public function messages(){
        return [
            'id_unit_measure.required'          => 'La :attribute es obligatorio',
            'id_unit_measure.integer'           => 'La :attribute debe ser un número',
            'id_unit_measure.exists'            => 'La :attribute seleccionado no es válido',

            'amount.required'                   => 'El :attribute es obligatorio',
            'amount.numeric'                    => 'El :attribute debe ser un número',

            'id_unit_measure_convert.required'  => 'La :attribute es obligatorio',
            'id_unit_measure_convert.integer'   => 'La :attribute debe ser un número',
            'id_unit_measure_convert.exists'    => 'La :attribute seleccionado no es válido',
        ];
    }

    public function attributes(){
        return [
            "id_unit_measure"           => "Unidad de Medida",
            "amount"                    => "Factor de conversión",
            "id_unit_measure_convert"   => "Unidad de Medida 2",
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
