<?php

namespace App\Http\Requests\DeviceNotifications;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\failedValidationWithName;


class StoreRequest extends FormRequest
{
    use failedValidationWithName; // gives response when validation failed

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $arr = [
            //  'subject' => 'required',
            // 'body' => 'nullable',
            // 'day' => 'nullable',
            // 'date' => 'nullable',
            'company_id' => 'required',
            'branch_id' => 'required',
            // 'frequency' => 'required',
            // 'time' => 'required',
            // 'reports' => 'array|min:1|max:5',
            // 'mediums' => 'array|min:1',
            // 'managers' => 'array|min:1',
            // 'tos' => 'array|min:1',
            // 'ccs' => 'array|nullable',
            // 'bccs' => 'array|nullable',
        ];


        return $arr;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company field is required',
            'reports.min' => 'Atleast 1 Report must be selected',
            'mediums.min' => 'Atleast 1 Medium must be selected',
            'tos.min' => 'Atleast 1 Email must be selected',
            'managers.min' => 'Atleast 1 Manager must be selected',
        ];
    }
}
