<?php

namespace App\Http\Requests\Products;

use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ProductRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){

        $rules = [];

        if (request()->isMethod('post')) {
            $codProductRule = "unique:products,cod_product";
        } elseif (request()->isMethod('put')) {
            $codProductRule = "unique:products,cod_product," . $this->id;
        }

        $rules["stock"] = "required|numeric";

        $rules = [
            // "cod_product"      => ["required", "string", "max:255", $codProductRule],
            "name"             => "required|string|max:255",
            "id_unit_measure"  => "required|integer|exists:units_measure,id",
            "price"            => "required|numeric",
            "price_purchase"   => "required|numeric",
        ];

        return $rules;

    }

    public function messages(){
        return [
            // 'cod_product.required'      => 'El :attribute es obligatorio',
            // 'cod_product.string'        => 'El :attribute debe ser una cadena de caracteres',
            // 'cod_product.max'           => 'El :attribute no debe exceder los :max caracteres',
            // 'cod_product.unique'        => 'El :attribute ya existe',

            'name.required'             => 'El :attribute es obligatorio',
            'name.string'               => 'El :attribute debe ser una cadena de caracteres',
            'name.max'                  => 'El :attribute no debe exceder los :max caracteres',

            'id_unit_measure.required'  => 'El :attribute es obligatorio',
            'id_unit_measure.integer'   => 'El :attribute debe ser un número',
            'id_unit_measure.exists'    => 'El :attribute seleccionado no es válido',

            'price.required'            => 'El :attribute es obligatorio',
            'price.numeric'             => 'El :attribute debe ser un número',
            'price.min'                 => 'El :attribute debe ser al menos :min',

            'price_purchase.required'   => 'El :attribute es obligatorio',
            'price_purchase.numeric'    => 'El :attribute debe ser un número',
            'price_purchase.min'        => 'El :attribute debe ser al menos :min',

            'stock.required'            => 'El :attribute es obligatorio',
            'stock.numeric'             => 'El :attribute debe ser un número',
        ];
    }

    public function attributes(){
        return [
            "cod_product"      => "código del producto",
            "name"             => "nombre del producto",
            "id_unit_measure"  => "unidad de medida",
            "price"            => "precio de venta",
            "price_purchase"   => "precio de compra",
            "stock"            => "stock",
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
