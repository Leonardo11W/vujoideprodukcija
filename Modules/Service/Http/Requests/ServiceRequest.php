<?php

namespace Modules\Service\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ServiceRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Ensure status is always present and converted to integer (0 or 1)
        $this->merge([
            'status' => (int) $this->input('status', 0),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'duration_min' => 'required|integer|min:1',
            'category_id' => 'required|integer|exists:categories,id',
            'default_price' => 'required|numeric|min:0',
            'status' => 'required|integer|in:0,1',
            'description' => 'nullable|string|max:250',
            'feature_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'existing_feature_image' => 'nullable|string',
            'custom_fields.*' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        $unifiedImageErrorMsg = __('messages.only_jpeg_jpg_png_gif_files_are_allowed_maximum_size_2_mb');
        return [
            'name.required' => __('service.name_required'),
            'name.max' => __('service.name_max_length'),
            'duration_min.required' => __('service.duration_required'),
            'duration_min.integer' => __('service.duration_numeric'),
            'duration_min.min' => __('service.duration_min_value'),
            'category_id.required' => __('service.category_required'),
            'category_id.exists' => __('service.category_exists'),
            'default_price.required' => __('service.price_required'),
            'default_price.numeric' => __('service.price_numeric'),
            'default_price.min' => __('service.price_min_value'),
            'description.max' => __('service.description_max_length'),
            // Unify all image validation messages (invalid format OR non-image OR size)
            'feature_image.image' => $unifiedImageErrorMsg,
            'feature_image.mimes' => $unifiedImageErrorMsg,
            'feature_image.max' => $unifiedImageErrorMsg,
            // Handles PHP/Symfony upload errors (e.g. > upload_max_filesize / post_max_size)
            'feature_image.uploaded' => $unifiedImageErrorMsg,
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
        $errors = $validator->errors();

        $data = [
            'success' => false,
            'message' => $errors->first(),
            'errors' => $errors->toArray(),
        ];

        if (request()->wantsJson() || request()->is('api/*') || request()->ajax()) {
            throw new HttpResponseException(response()->json($data, 422));
        }


        throw new HttpResponseException(
            redirect()->back()->withInput()->withErrors($validator)
        );    
    }
}
