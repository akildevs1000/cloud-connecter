<?php

namespace App\Http\Requests\PayrollFormula;

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
            'salary_type' => 'required|string',
            'ot_value' => 'required|numeric',
            'deduction_value' => 'required|numeric',
            'company_id' => 'required|numeric',
            'branch_id' => 'required|numeric',
        ];
    }
}
