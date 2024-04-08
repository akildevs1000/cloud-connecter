<?php

namespace App\Http\Requests\Employee;

use App\Traits\failedValidationWithName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    use failedValidationWithName;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_id' => ['required'],
            'employee_id' => ['required'],
            'system_user_id' => ['required', 'regex:/^[1-9][0-9]*$/'],
            'full_name' => ['nullable', 'min:3', 'max:100'],
            'display_name' => ['required', 'min:3', 'max:50'],
            'first_name' => ['required', 'min:3', 'max:50'],
            'last_name' => ['required', 'min:3', 'max:50'],
            'title' => ['required'],
            'status' => ['nullable'],
            'department_id' => ['required'],
            'sub_department_id' => ['nullable'],
            'designation_id' => ['nullable'],
            'employee_id' => ['required'],
            'leave_group_id' => ['nullable'],
            'reporting_manager_id' => ['nullable'],
            'branch_id' => ['required'],
            'profile_picture' => ['image', 'mimes:jpeg,png,jpg,svg', 'max:2048', 'sometimes', 'nullable'],
            // 'phone_number' =>  ['required', 'min:10', 'max:12'],
            // 'whatsapp_number' => ['required', 'min:10', 'max:12'],
        ];
    }

    public function messages()
    {
        return [
            'system_user_id.regex' => 'The employee device ID should not start with zero.',
        ];
    }
}
