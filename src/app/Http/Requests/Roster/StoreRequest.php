<?php

namespace App\Http\Requests\Roster;

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
        return [
            'name' => ["required"],
            // 'json' => ["required", "array"],
            'shift_ids' => ["required", "array", "min:7"],
            'shift_names' => ["required", "array", "min:7"],
            'days' => ["required", "array", "min:7"],
            'company_id' => ["required"],
        ];
    }


    public function messages()
    {
        return [
            "shift_ids.required" => "The shift field is required.",
            "shift_ids.min" => "The shift field is required"
        ];
    }
}
