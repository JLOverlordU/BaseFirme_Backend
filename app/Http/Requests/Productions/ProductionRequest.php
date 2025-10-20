<?php

namespace App\Http\Requests\Productions;

use App\Rules\ActiveUser;
use App\Rules\ActiveShift;
use App\Rules\ActiveMachine;
use App\Rules\ActiveFormula;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ProductionRequest extends FormRequest{

    public function authorize(){
        return true;
    }

    public function rules(){
        
        if (request()->isMethod('post')) {
            $rules['user_id'] = ['required', 'integer', new ActiveUser()];
            $rules['date'] = 'required|date';
        }

        $rules = [
            'shift_id'          => ['required', 'integer', new ActiveShift()],
            'machine_id'        => ['required', 'integer', new ActiveMachine()],
            'tons_produced'     => 'required|numeric|min:0',
            'formula_id'        => ['required', 'integer', new ActiveFormula()],
            'packing'           => 'required|numeric|min:0',
            'observations'      => 'nullable|string|max:255',
        ];

        return $rules;

    }

    public function messages(){
        return [
            'date.required'             => 'La :attribute es obligatoria',
            'date.date'                 => 'La :attribute debe ser una fecha válida',

            'user_id.required'          => 'El :attribute es obligatorio',
            'user_id.integer'           => 'El :attribute es obligatorio',
            'user_id.exists'            => 'El :attribute seleccionado no es válido',

            'shift_id.required'         => 'El :attribute es obligatorio',
            'shift_id.integer'          => 'El :attribute es obligatorio',

            'machine_id.required'       => 'La :attribute es obligatoria',
            'machine_id.integer'        => 'La :attribute es obligatoria',

            'tons_produced.required'    => 'Las :attribute son obligatorias',
            'tons_produced.numeric'     => 'Las :attribute deben ser un número',
            'tons_produced.min'         => 'Las :attribute deben ser al menos :min',

            'formula_id.required'       => 'La :attribute es obligatoria',
            'formula_id.integer'        => 'La :attribute es obligatoria',

            'packing.required'          => 'El :attribute son obligatorias',
            'packing.numeric'           => 'El :attribute deben ser un número',
            'packing.min'               => 'El :attribute deben ser al menos :min',

            'observations.string'       => 'Las observaciones deben ser una cadena de caracteres',
            'observations.max'          => 'Las observaciones no deben exceder los :max caracteres',
        ];
    }

    public function attributes(){
        return [
            'date'              => 'fecha',
            'user_id'           => 'usuario',
            'shift_id'          => 'turno',
            'machine_id'        => 'máquina',
            'tons_produced'     => 'toneladas producidas',
            'formula_id'        => 'fórmula',
            'packing'           => 'empaque',
            'observations'      => 'observaciones',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(
            SendResponse::jsonMessage('error', 'validar', $errors, 400)
        );
    }

}
