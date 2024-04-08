<?php

namespace App\Http\Requests\VisitorLog;

use App\Traits\failedValidationWithName;
use Illuminate\Foundation\Http\FormRequest;

class Store extends FormRequest
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
            'UserID' => 'required|integer',
            'LogTime' => 'required|date_format:Y-m-d H:i',
            'DeviceID' => 'required|string|max:20',
            'company_id' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'UserID.required' => 'The UserID field is required.',
            'UserID.integer' => 'The UserID must be an integer.',
            'LogTime.required' => 'The LogTime field is required.',
            'LogTime.date_format' => 'The LogTime must be in the format Y-m-d H:i.',
            'DeviceID.required' => 'The DeviceID field is required.',
            'DeviceID.string' => 'The DeviceID must be a string.',
            'DeviceID.max' => 'The DeviceID may not be greater than :max characters.',
            'company_id.required' => 'The company_id field is required.',
            'company_id.integer' => 'The company_id must be an integer.',
        ];
    }
}
