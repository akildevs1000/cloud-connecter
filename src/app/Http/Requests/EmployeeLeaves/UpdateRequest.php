<?php

namespace App\Http\Requests\EmployeeLeaves;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'company_id' => 'required',
            'employee_id' => 'required',
            'leave_type_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'required',
            'reporting_manager_id' => 'required',
        ];
    }
}