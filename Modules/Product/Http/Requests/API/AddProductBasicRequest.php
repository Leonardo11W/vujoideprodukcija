<?php
# Form request for basic level product creation.
namespace Modules\Product\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddProductBasicRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_image_url' => ['nullable', 'url', 'max:2048'],
            'product_name' => ['required', 'string', 'min:1', 'max:255'],
            'product_short_description' => ['required', 'string', 'min:1', 'max:500'],
            'product_description' => ['nullable', 'string', 'max:20000'],
            'product_brand_name' => ['required', 'string', 'min:1', 'max:128'],
            'product_categories' => ['required', 'array', 'min:1'],
            'product_categories.*' => ['string', 'max:128'],
            'product_tags' => ['required', 'array', 'min:1'],
            'product_tags.*' => ['string', 'max:50'],
            'product_unit' => ['required', 'integer', 'min:1'],
            'product_status' => ['required', 'boolean'],
            'product_is_featured' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422));
    }
}
