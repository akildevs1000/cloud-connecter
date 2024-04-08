<?php

namespace App\Http\Requests\Employee;

use App\Http\Controllers\Controller;
use App\Traits\failedValidationWithName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $controller = new Controller;

        return [
            'department_id' => ['required'],
            'company_id' => ['required'],
            'employee_id' => [
                'required',
                'regex:/^[1-9][0-9]*$/',
                // "unique:attendance_logs,UserID",
                $controller->uniqueRecord("employees", "employee_id", $this->employee_id),
                $controller->uniqueRecord("attendance_logs", "UserID", $this->employee_id),
            ],
            'system_user_id' => [
                'required',
                'regex:/^[1-9][0-9]*$/',
                $controller->uniqueRecord("employees", "system_user_id", $this->system_user_id),
                $controller->uniqueRecord("attendance_logs", "UserID", $this->system_user_id),
            ],
            'full_name' => ['nullable', 'min:3', 'max:100'],
            'display_name' => ['required', 'min:3', 'max:20'],
            'first_name' => ['required', 'min:3', 'max:20'],
            'last_name' => ['required', 'min:3', 'max:20'],
            'title' => ['required'],
            'joining_date' => ['required'],
            'phone_number' =>  ['required', 'min:10', 'max:13'],
            'whatsapp_number' => ['nullable', 'min:10', 'max:13'],
            'status' => ['nullable'],
            'branch_id' => ['required'],
            'email' => 'nullable|min:3|max:191|unique:users',
            'profile_picture' => ['image', 'mimes:jpeg,png,jpg,svg', 'max:2048', 'sometimes', 'nullable'],
        ];
    }

    public function messages()
    {
        return [
            'system_user_id.regex' => 'The employee device ID should not start with zero.',
        ];
    }
}
