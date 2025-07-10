<?php

namespace App\Http\Requests;

use App\Enums\GenderEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class MembershipSignupRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'dob'               => 'required|string|before:today',
            'gender'            => ['required', 'string', new Enum(GenderEnum::class)],
            'mobile'            => 'required|string|max:20',
            'whatsapp_no'       => 'required|string|max:20',
            'email'             => 'required|email|unique:users,email',
            'pin'               => 'required|string|max:10',
            'address'           => 'required|string|max:500',
            'state'             => 'required|string|max:100',
            'district'          => 'required|string|max:100',
            'teaching_exp'      => 'required|integer|min:0',
            'qualification'     => ['required', 'array', 'min:1'],
            'qualification.*'   => ['string', 'max:255'],
            'area_of_work'      =>  ['required', 'array', 'min:1'],
            'area_of_work.*'    => ['string'],
            'password'          => 'required|string|min:6|confirmed',
            'membership_type'   => 'required|in:Individual,Institutional',
            'membership_plan'   => 'required|in:Annual,LongTerm,Overseas',
            'has_member_any'=>['sometimes','nullable', 'boolean'],
            'name_association'=>['nullable','string'],
            'expectation'=>['nullable','string'],
            'has_newsletter'=>['sometimes','nullable', 'boolean'],
            'title'=>['nullable','string'],
            'address_institution'=>['nullable','string'],   
            'name_institution'=>['nullable','string'],
            'type_institution'=>['nullable','string'],
            'other_institution'=>['nullable','string'],
            'contact_person'=>['nullable','string'],
        ];
    }
}
