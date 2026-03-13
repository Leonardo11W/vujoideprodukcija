<?php

namespace Modules\Product\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddProductInventoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_has_variations' => ['required', 'boolean'],
            
            // Simple product fields
            'product_price_included_tax' => ['required_if:product_has_variations,false', 'numeric', 'min:0'],
            'product_stock' => ['required_if:product_has_variations,false', 'integer', 'min:0'],
            'product_sku' => ['nullable', 'string', 'max:128'],
            'product_code' => ['nullable', 'string', 'max:128'],

            // Variation groups (optional but recommended for mapping)
            'product_variation_groups' => ['nullable', 'array'],
            'product_variation_groups.*.variation_type' => ['required_with:product_variation_groups', 'string', 'max:128'],
            'product_variation_groups.*.variation_values' => ['required_with:product_variation_groups', 'array', 'min:1'],
            'product_variation_groups.*.variation_values.*' => ['string', 'max:128'],

            // Variants array
            'product_variants' => ['required_if:product_has_variations,true', 'array'],
            'product_variants.*.variation_map' => ['required_with:product_variants', 'array'],
            'product_variants.*.price_included_tax' => ['required_with:product_variants', 'numeric', 'min:0'],
            'product_variants.*.stock' => ['required_with:product_variants', 'integer', 'min:0'],
            'product_variants.*.sku' => ['nullable', 'string', 'max:128'],
            'product_variants.*.code' => ['nullable', 'string', 'max:128'],
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
