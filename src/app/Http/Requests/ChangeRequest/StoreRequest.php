<?php

namespace App\Http\Requests\ChangeRequest;

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
            'request_type' => "required",
            'from_date' => "required",
            'to_date' => 'required',
            'requested_at' => 'required',
            'remarks' => "nullable|max:100",
            'status' => "nullable",
            'attachment' => "nullable|file",
            'employee_device_id' => "required",
            'branch_id' => "required",
            'company_id' => "required",
        ];
    }
}
