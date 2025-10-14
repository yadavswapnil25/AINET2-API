<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class StorePpfRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'main_title' => 'required|string',
            'main_name' => 'required|string',
            'main_work' => 'required|string',
            'presenter_main_country_code' => 'required|string',
            'main_phone' => 'required|string',
            'presenter_main_email' => 'required|email',
            'pr_area' => 'required|string',
            'pr_nature' => 'required|string',
            'pr_abstract' => 'required|string',
            'presenter_bio' => 'required|string',
            'pr_title' => 'required|string',

            // Optional co-presenters
            'co1_title' => 'nullable|string',
            'co1_name' => 'nullable|string',
            'co1_work' => 'nullable|string',
            'co1_country_code' => 'nullable|string',
            'co1_phone' => 'nullable|string',
            'co1_email' => 'nullable|email',

            'co2_title' => 'nullable|string',
            'co2_name' => 'nullable|string',
            'co2_work' => 'nullable|string',
            'co2_country_code' => 'nullable|string',
            'co2_phone' => 'nullable|string',
            'co2_email' => 'nullable|email',

            'co3_title' => 'nullable|string',
            'co3_name' => 'nullable|string',
            'co3_work' => 'nullable|string',
            'co3_country_code' => 'nullable|string',
            'co3_phone' => 'nullable|string',
            'co3_email' => 'nullable|email',

            'pr_area_specify' => 'nullable|string',
            'co_presenter_1_bio' => 'nullable|string',
            'co_presenter_2_bio' => 'nullable|string',
            'pr3_bio' => 'nullable|string',
        ];
    }
}



