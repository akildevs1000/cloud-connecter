<?php

namespace App\Http\Requests\Holidays;

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
            'name' => 'required|min:4|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'total_days' => 'required',
            'company_id' => 'required',
            'branch_id' => 'required',
        ];
    }
}
