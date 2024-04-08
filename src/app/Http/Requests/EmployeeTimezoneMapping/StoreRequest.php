<?php

namespace App\Http\Requests\EmployeeTimezoneMapping;

use App\Traits\failedValidationWithName;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'timezone_id' => 'required',
            'employee_id' => 'required|array',
            'device_id' => 'required|array',
            'employee_ids' => 'required|array',
            'device_ids' => 'required|array',
            'company_id' => 'required',
            'branch_id' => 'required',
        ];
    }
}
