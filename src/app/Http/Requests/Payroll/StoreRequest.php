<?php

namespace App\Http\Requests\Payroll;

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
            'basic_salary' => ['required', 'numeric'],
            'net_salary' => ['nullable'],
            'effective_date' => 'required|date_format:Y-m-d',
            'earnings' => ['nullable', 'array'],
            'earnings.*.label' => ['required', 'string'],
            'earnings.*.value' => ['required', 'numeric'],
            'employee_id' => ["required"],
            'company_id' => ["required"],
        ];
    }
}
