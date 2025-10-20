<?php

namespace App\Http\Requests\Administrable\ClientsProviders;

use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ClientProviderRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){
        
        return [
            "name"        => "required|string|max:255",
            "phone"         => "required|integer",
            // "email"       => "required|string|email|max:255",
            "address"     => "required|string",
            "description" => "nullable|string",
        ];

    }

    public function messages(){
        return [
            'name.required'     => 'El :attribute es obligatorio',
            'name.string'       => 'El :attribute debe ser una cadena de caracteres',
            'name.max'          => 'El :attribute no debe exceder los :max caracteres',

            'email.required'    => 'El :attribute es obligatorio',
            'email.string'      => 'El :attribute debe ser una cadena de caracteres',
            'email.email'       => 'El :attribute debe ser una dirección de correo electrónico válida',
            'email.max'         => 'El :attribute no debe exceder los :max caracteres',

            'phone.required'    => 'El :attribute es obligatorio',
            'phone.integer'     => 'El :attribute debe ser un número',

            'address.required'  => 'La :attribute es obligatoria',
            'address.string'    => 'La :attribute debe ser una cadena de caracteres',

            'description.string' => 'La :attribute debe ser una cadena de caracteres',
        ];
    }

    public function attributes(){
        return [
            "name"          => "nombre",
            "email"         => "correo",
            "address"       => "dirección",
            "phone"         => "teléfono",
            "description"   => "descripción",
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
