<?php

namespace App\Http\Requests\CompanyBranch;

use App\Traits\failedValidationWithName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    use failedValidationWithName;

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
            'logo' => ['image', 'mimes:jpeg,png,jpg,svg', 'max:2048', 'sometimes', 'nullable'],
            'branch_name' => ['required', 'min:3', 'max:20'],
            'user_id' => ['required'],
            'licence_number' => ['nullable', 'min:3', 'max:20'],
            'licence_issue_by_department' => ['nullable', 'min:3', 'max:20'],
            'licence_expiry' => ['nullable'],
            'lat' => 'nullable|min:3|max:100',
            'lon' => 'nullable|min:3|max:100',
            'address' => 'nullable|min:3|max:300',
            'company_id' => 'required',
        ];
    }
}
