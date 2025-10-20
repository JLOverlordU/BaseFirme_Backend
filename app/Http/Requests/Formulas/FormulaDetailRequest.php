<?php

namespace App\Http\Requests\Formulas;

use App\Rules\ActiveFormula;
use App\Rules\ActiveProduct;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class FormulaDetailRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){

        $rules = [
            'details.*.product_id'          => ['required', 'integer', new ActiveProduct()],
            'details.*.price'               => 'required|numeric|min:0',
            'details.*.amount'              => 'required|numeric',
            'details_nucleos.*.product_id'  => ['required', 'integer', new ActiveProduct()],
            'details_nucleos.*.price'       => 'required|numeric|min:0',
            'details_nucleos.*.amount'      => 'required|numeric',
        ];

        return $rules;

    }

    public function messages(){
        return [
            'details.*.product_id.required'         => 'El :attribute es obligatorio.',
            'details.*.product_id.integer'          => 'El :attribute debe ser un número entero.',

            'details.*.price.required'              => 'La :attribute es obligatoria.',
            'details.*.price.numeric'               => 'La :attribute debe ser un número.',
            'details.*.price.min'                   => 'La :attribute debe ser al menos :min.',

            'details.*.amount.required'             => 'La :attribute es obligatoria.',
            'details.*.amount.numeric'              => 'La :attribute debe ser un número.',

            'details_nucleos.*.product_id.required' => 'El :attribute es obligatorio.',
            'details_nucleos.*.product_id.integer'  => 'El :attribute debe ser un número entero.',

            'details_nucleos.*.price.required'      => 'La :attribute es obligatoria.',
            'details_nucleos.*.price.numeric'       => 'La :attribute debe ser un número.',
            'details_nucleos.*.price.min'           => 'La :attribute debe ser al menos :min.',

            'details_nucleos.*.amount.required'     => 'La :attribute es obligatoria.',
            'details_nucleos.*.amount.numeric'      => 'La :attribute debe ser un número.',
        ];
    }

    public function attributes(){
        return [
            'details.*.product_id'          => 'producto',
            'details.*.price'               => 'precio',
            'details.*.amount'              => 'cantidad',
            'details_nucleos.*.product_id'  => 'producto',
            'details_nucleos.*.price'       => 'precio',
            'details_nucleos.*.amount'      => 'cantidad',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
