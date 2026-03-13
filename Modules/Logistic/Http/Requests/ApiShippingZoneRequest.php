<?php

namespace Modules\Logistic\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_zone_name' => 'required|string|min:1|max:255',
            'shipping_zone_address' => 'required|string|min:1|max:2000',
            'shipping_zone_phone' => 'required|string|min:7|max:20',
            'shipping_zone_logistic' => 'required',
            'shipping_zone_country' => 'required',
            'shipping_zone_state' => 'required',
            'shipping_zone_cities' => 'required|array|min:1',
            'shipping_zone_cities.*' => 'required',
            'shipping_zone_standard_delivery_charge' => 'required|numeric|min:0',
            'shipping_zone_standard_delivery_time' => 'required|string|max:64',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => false,
            'message' => $validator->errors()->first(),
            'all_message' => $validator->errors(),
        ];

        if ($this->wantsJson() || $this->is('api/*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(
            redirect()->back()->withInput()->with('errors', $validator->errors())
        );
    }
}


