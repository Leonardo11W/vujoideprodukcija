<?php

namespace Modules\Logistic\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LogisticRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'logistics_name' => 'required|string|min:1|max:255',
            'logistics_status' => 'required|boolean',
            'logistics_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * This ensures API-friendly JSON responses.
     */
    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => false,
            'message' => $validator->errors()->first(),
            'all_message' => $validator->errors(),
        ];

        if (request()->wantsJson() || request()->is('api/*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(
            redirect()->back()->withInput()->with('errors', $validator->errors())
        );
    }
}
