<?php

namespace App\Http\Requests\Formulas;

use App\Rules\ActiveUnitMeasure;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class FormulaRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){
        
        $rules = [
            'name'            => 'required|string|max:255',
            // 'unit_measure_id' => ['required', 'integer', new ActiveUnitMeasure()],
            'total'           => 'required|numeric|min:0',
            'details'         => 'required|array',

            'price'             => 'required|numeric',
            'price_purchase'    => 'required|numeric',
            "equivalent"        => "required|numeric|min:0",
            "stock"             => "required|numeric",
        ];

        return $rules;

    }

    public function messages(){
        return [
            'name.required'            => 'El nombre de la fórmula es obligatorio',
            'name.string'              => 'El nombre debe ser una cadena de texto',
            'name.max'                 => 'El nombre no debe exceder los :max caracteres',

            'unit_measure_id.required' => 'La unidad de medida es obligatoria',
            'unit_measure_id.integer'  => 'La unidad de medida debe ser un número',
            'unit_measure_id.exists'   => 'La unidad de medida seleccionada no es válida',

            'total.required'           => 'El total es obligatorio',
            'total.numeric'            => 'El total debe ser un número',
            'total.min'                => 'El total debe ser al menos :min',

            'details.required'         => 'Los detalles son obligatorios.',
            'details.array'            => 'Los detalles deben ser un array.',

            'price.required'            => 'El :attribute es obligatorio',
            'price.numeric'             => 'El :attribute debe ser un número',
            'price.min'                 => 'El :attribute debe ser al menos :min',

            'price_purchase.required'   => 'El :attribute es obligatorio',
            'price_purchase.numeric'    => 'El :attribute debe ser un número',
            'price_purchase.min'        => 'El :attribute debe ser al menos :min',

            'equivalent.required'       => 'El :attribute es obligatorio',
            'equivalent.numeric'        => 'El :attribute debe ser un número',
            'equivalent.min'            => 'El :attribute debe ser al menos :min',

            'stock.required'            => 'El :attribute es obligatorio',
            'stock.numeric'             => 'El :attribute debe ser un número',

            'converted_stock.numeric'   => 'El :attribute debe ser un número',
        ];
    }

    public function attributes(){
        return [
            'name'            => 'nombre de la fórmula',
            'unit_measure_id' => 'unidad de medida',
            'total'           => 'total',
            'details'         => 'detalles',
            "price"           => "precio de venta",
            "price_purchase"  => "precio de compra",
            "equivalent"      => "equivalente",
            "stock"           => "stock (SACO/UND)",
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
