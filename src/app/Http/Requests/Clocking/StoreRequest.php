<?php

namespace App\Http\Requests\Clocking;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
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
            'clock_type' => "required",
            'user_id' => "required",
            'attachment' => 'nullable|file',
            'remarks' => "nullable",
            'branch_id' => "required",
            'company_id' => "required",
        ];
    }
}
