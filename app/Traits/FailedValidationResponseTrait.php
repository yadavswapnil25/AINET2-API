<?php

namespace App\Traits;

use App\Traits\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait FailedValidationResponseTrait
{
    use Response;

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator    $validator
     * @return JsonResponse
     *
     * @throws HttpResponseException
     */
    public function failedValidation(Validator $validator): JsonResponse
    {
        $extra = [];
        $messages = Arr::undot($validator->errors()->messages());

        if (in_array(\Illuminate\Support\Facades\Route::getFacadeRoot()?->current()?->uri(), ['api/v1/portal/events/create', 'api/v1/portal/events/update'])) {
            if ($this->registration_method && (isset($this->registration_method['website_registration_method']) && $this->registration_method['website_registration_method'] == 'external') && isset($messages['third_parties'])) { // Set validation errors to website_registration_method when third_parties validation fails
                $extra['registration_method']['website_registration_method'] = 'There is an issue with the registration method configuration';
            }

            if ($this->registration_method && (isset($this->registration_method['portal_registration_method']) && $this->registration_method['portal_registration_method'] == 'external') && isset($messages['third_parties'])) { // Set validation errors to portal_registration_method when third_parties validation fails
                $extra['registration_method']['portal_registration_method'] = 'There is an issue with the registration method configuration';
            }
        }

        $needles = [
            "must be a string",
            "required",
            "invalid",
            "not found",
            "The event category does not belong to the event. Please refresh your browser and try again",
            "already registered for the event",
        ];

        if (isset($this->eec)) {
            if (is_array($this->eec)) {
                if (isset($messages['eec']) && isset($messages['eec'][0])) { // Check if 'eec' key exists in $messages
                    foreach ($messages['eec'] as $key => $eec) {
                        if (isset($eec['ref']) && isset($eec['ref'][0]) && Str::contains($eec['ref'][0], $needles)) {
                            $messages['eec'][$key]['create_enquiry'] = false;
                        } else {
                            $messages['eec'][$key]['create_enquiry'] = true;
                        }
                    }
                }
            } elseif (is_string($this->eec)) {
                if (isset($messages['eec']) && isset($messages['eec'][0])) { // Check if 'eec' key exists in $messages
                    if (isset($messages['eec'][0]) && Str::contains($messages['eec'][0], $needles)) {
                        $messages['create_enquiry'] = false;
                    } else {
                        $messages['create_enquiry'] = true;
                    }
                }
            }
        }

        throw new HttpResponseException(
            $this->error('Please resolve the warnings!', 422, array_merge($messages, $extra))
        );
    }
}
