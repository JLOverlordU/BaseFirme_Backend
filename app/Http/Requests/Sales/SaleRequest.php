<?php

namespace App\Http\Requests\Sales;

use App\Rules\ActiveClient;
use App\Rules\ActiveProduct;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SaleRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){

        $rules = [
            'client_id'             => ['required', 'integer', new ActiveClient()],
            'description'           => 'nullable|string|max:255',
            'details'               => 'required|array|min:1',
            'details.*.product_id'  => ['required', 'integer', new ActiveProduct()],
            'details.*.price'       => 'required|numeric',
            'details.*.amount'      => 'required|numeric',
            // 'deposit'               => 'required|numeric',
            // 'consumption'           => 'required|numeric',
            'total'                 => 'required|numeric',
        ];

        return $rules;

    }

    public function messages(){
        return [
            'client_id.required'             => 'El :attribute es obligatorio',
            'client_id.integer'              => 'El :attribute es obligatorio',
            'client_id.exists'               => 'El :attribute seleccionado no es válido',

            'description.string'             => 'Las observaciones deben ser una cadena de caracteres',
            'description.max'                => 'Las observaciones no deben exceder los :max caracteres',

            'details.required'               => 'Debe incluir al menos un :attribute en la venta',
            'details.array'                  => 'El formato de los :attribute no es válido',
            'details.min'                    => 'Debe seleccionar al menos un detalle para la venta',

            'details.*.product_id.required'  => 'El :attribute es obligatorio.',
            'details.*.product_id.integer'   => 'El :attribute debe ser un número entero.',

            'details.*.price.required'       => 'El :attribute es obligatorio.',
            'details.*.price.numeric'        => 'El :attribute debe ser un número.',
            // 'details.*.price.min'            => 'El :attribute debe ser al menos :min.',

            'details.*.amount.required'      => 'La :attribute es obligatoria.',
            'details.*.amount.numeric'       => 'La :attribute debe ser un número.',
            // 'details.*.amount.min'           => 'La :attribute debe ser al menos :min.',

            'deposit.required'               => 'El :attribute es obligatorio',
            'deposit.numeric'                => 'El :attribute debe ser un número',
            // 'deposit.min'                    => 'El :attribute debe ser al menos :min',

            'consumption.required'           => 'El :attribute es obligatorio',
            'consumption.numeric'            => 'El :attribute debe ser un número',
            // 'consumption.min'                => 'El :attribute debe ser al menos :min',

            'total.required'                 => 'El :attribute es obligatorio',
            'total.numeric'                  => 'El :attribute debe ser un número',
            // 'total.min'                      => 'El :attribute debe ser al menos :min',
        ];
    }

    public function attributes(){
        return [
            'client_id'             => 'cliente',
            'description'           => 'descripción',
            'details'               => 'detalle',
            'details.*.product_id'  => 'producto',
            'details.*.price'       => 'precio',
            'details.*.amount'      => 'cantidad',
            'deposit'               => 'depositó',
            'consumption'           => 'consumo',
            'total'                 => 'total de la venta',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
