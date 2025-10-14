<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class StoreDrfRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member' => 'required|string',
            'name' => 'required|string',
            'gender' => 'required|string',
            'age' => 'required|integer|min:0',
            'institution' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'pincode' => 'required|string',
            'state' => 'required|string',
            'country_code' => 'required|string',
            'phone_no' => 'required|string',
            'email' => 'required|email',
            'areas' => 'nullable|array',
            'areas.*' => 'string',
            'other' => 'nullable|string',
            'experience' => 'nullable|string',
            'conference' => 'required|string',
            'types' => 'nullable|array',
            'types.*' => 'string',
            'you_are_register_as' =>'required|string',
            'pre_title'=>'required|string'
        ];
    }
}



