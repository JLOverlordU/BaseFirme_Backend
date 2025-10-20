<?php

namespace App\Http\Requests\Purchases;

use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PurchaseDepositRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){

        $rules = [
            'amount'    => 'required|numeric',
        ];

        return $rules;

    }

    public function messages(){
        return [
            'amount.required'   => 'La :attribute es obligatoria.',
            'amount.numeric'    => 'La :attribute debe ser un número.',
            'amount.min'        => 'La :attribute debe ser al menos :min.',
        ];
    }

    public function attributes(){
        return [
            'amount'   => 'cantidad',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
