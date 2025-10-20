<?php

namespace App\Http\Requests\Maestras;

use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PresentationRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){

        return [
            "name"  => "required|string",
        ];
    }

    public function messages(){
        return [
            'name.required' => 'El :attribute es obligatorio',
            'name.string'   => 'El :attribute debe ser una cadena de caraceteres',
        ];
    }

    public function attributes(){
        return [
            "name"  => "nombre",
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
