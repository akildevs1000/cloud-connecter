<?php

namespace App\Http\Requests\Visa;

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
            "visa_no" => "required|min:2|max:20",
            "place_of_issues" => "required|min:1|max:20",
            "country" => "required|min:1|max:20",
            "issue_date" => "required",
            "expiry_date" => "required",

            "security_amount" => "nullable",
            "labour_no" => "required",
            "personal_no" => "nullable",
            "labour_issue_date" => "required",
            "labour_expiry_date" => "required",
            "note" => "required",

            "employee_id" => "required",
            "company_id" => "required",
        ];
    }
}
