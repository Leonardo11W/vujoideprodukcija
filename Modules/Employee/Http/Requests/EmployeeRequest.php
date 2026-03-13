<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function rules()
    {
        // Get the employee ID from route (could be 'employee' or 'id' parameter)
        $employeeId = $this->route('employee') ?? $this->route('id');
        
        // If it's a model instance, get the ID
        if (is_object($employeeId)) {
            $employeeId = $employeeId->id;
        }

        // ✅ UPDATE (when employee ID exists in route)
        if ($employeeId) {
            return [
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    Rule::unique('users', 'email')
                        ->ignore($employeeId)
                        ->whereNull('deleted_at'),
                ],
                // Either mobile OR phone_number
                'mobile' => 'required_without:phone_number|string',
                'phone_number' => 'required_without:mobile|string',
                // Password is optional in update mode
                'password' => 'nullable|min:8',
                'confirm_password' => 'nullable|same:password',
                'branch_id' => 'nullable|integer|exists:branches,id',
                'gender' => 'nullable|string',
                'service_id' => 'nullable|string',
                'commission_id' => 'nullable|integer|exists:commissions,id',
                // AppServiceProvider sets Schema::defaultStringLength(191) so these are VARCHAR(191)
                'about_self' => 'nullable|string|max:191',
                'expert' => 'nullable|string|max:191',
                'facebook_link' => 'nullable|string|max:191|url',
                'instagram_link' => 'nullable|string|max:191|url',
                'twitter_link' => 'nullable|string|max:191|url',
                'dribbble_link' => 'nullable|string|max:191|url',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'status' => 'nullable|boolean',
                'show_in_calender' => 'nullable|boolean',
                'is_manager' => 'nullable|boolean',
                'manager_ids' => 'nullable|string',
            ];
        }

        // ✅ CREATE
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'mobile' => 'required_without:phone_number|string',
            'phone_number' => 'required_without:mobile|string',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'gender' => 'nullable|string',
            'service_id' => 'nullable|string',
            'commission_id' => 'nullable|integer|exists:commissions,id',
            // AppServiceProvider sets Schema::defaultStringLength(191) so these are VARCHAR(191)
            'about_self' => 'nullable|string|max:191',
            'expert' => 'nullable|string|max:191',
            'facebook_link' => 'nullable|string|max:191|url',
            'instagram_link' => 'nullable|string|max:191|url',
            'twitter_link' => 'nullable|string|max:191|url',
            'dribbble_link' => 'nullable|string|max:191|url',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|boolean',
            'show_in_calender' => 'nullable|boolean',
            'is_manager' => 'nullable|boolean',
            'manager_ids' => 'nullable|string',
        ];
    }

    public function authorize()
    {
        return true;
    }

    // 🚨 Prevent redirect / HTML response
    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => false,
            'message' => $validator->errors()->first(),
            'all_message' => $validator->errors(),
        ];

        if (request()->wantsJson() || request()->is('api/*') || request()->ajax()) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
