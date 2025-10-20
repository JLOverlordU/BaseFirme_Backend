<?php

namespace App\Http\Requests\Administrable\Users;

use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UserRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){
        
        if(request()->isMethod('post')) {
            $slugRule = "unique:users,username";
        } elseif (request()->isMethod('put')){
            $slugRule = "unique:users,username,".$this->id;
        }

        return [
            "username"  => ["required", "string", "min:8", $slugRule],
            "name"      => "required|string|max:255",
            "email"     => "required|string|email|max:255",
            "phone"     => "required|integer",
            "password"  => "required|string|min:6",
            "role_id"   => "required|integer|exists:roles,id"
            // "password" => ["required", "string", "min:8", "regex:/[a-z]/", "regex:/[A-Z]/", "regex:/[0-9]/", "regex:/[@$!%*#?&]/"],
        ];

    }

    public function messages(){
        return [
            'username.required' => 'El :attribute es obligatorio',
            'username.string'   => 'El :attribute debe ser una cadena de caraceteres',
            'username.min'      => 'El :attribute debe tener al menos :min caracteres.',
            'username.unique'   => 'El :attribute ya existe',

            'name.required'     => 'El :attribute es obligatorio',
            'name.string'       => 'El :attribute debe ser una cadena de caraceteres',
            'name.max'          => 'El :attribute no debe exceder los :max caracteres',

            'email.required'    => 'El :attribute es obligatorio',
            'email.string'      => 'El :attribute debe ser una cadena de caraceteres',
            'email.email'       => 'El :attribute debe ser una dirección de correo electrónico válida',
            'email.max'         => 'El :attribute no debe exceder los :max caracteres',

            'phone.required'    => 'El :attribute es obligatorio',
            'phone.integer'     => 'El :attribute debe ser un número',

            'password.required' => 'La :attribute es obligatoria',
            'password.string'   => 'La :attribute debe ser una cadena de caraceteres',
            'password.min'      => 'La :attribute debe tener al menos :min caracteres.',

            'role_id.required'  => 'El :attribute es obligatorio',
            'role_id.integer'   => 'El :attribute debe ser un número',
            'role_id.exists'    => 'El :attribute seleccionado no es válido',
        ];
    }

    public function attributes(){
        return [
            "username"  => "nombre de usuario",
            "name"      => "nombre",
            "email"     => "correo",
            "phone"     => "teléfono",
            "password"  => "contraseña",
            "role_id"   => "rol",
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
