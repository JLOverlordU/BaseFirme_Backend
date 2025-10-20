<?php

namespace App\Http\Requests\Administrable\Users;

use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RoleRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){
        
        // if(request()->isMethod('post')) {
        //     $slugRule = "unique:roles,slug";
        // } elseif (request()->isMethod('put')){
        //     $slugRule = "unique:roles,slug,".$this->id;
        // }

        return [
            "name"  => "required|string",
            // "slug"  => ["required", "string", $slugRule]
        ];

    }

    public function messages(){
        return [
            'name.required' => 'El :attribute es obligatorio',
            'name.string'   => 'El :attribute debe ser una cadena de caraceteres',

            // 'slug.required' => 'El :attribute es obligatorio',
            // 'slug.string'   => 'El :attribute debe ser una cadena de caraceteres',
            // 'slug.unique'   => 'El :attribute ya existe',
        ];
    }

    public function attributes(){
        return [
            "name"  => "nombre",
            // "slug"  => "slug"
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
