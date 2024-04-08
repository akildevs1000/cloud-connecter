<?php

namespace App\Http\Requests\Visitor;

use Illuminate\Foundation\Http\FormRequest;

class UploadVisitor extends FormRequest
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
        $validations = [];
        $validations['first_name'] = 'required|string|max:255';
        $validations['last_name'] = 'required|string|max:255';
        // $validations['logo'] = 'required|image|mimes:jpeg,png,jpg,gif';
        $validations['logo'] = 'required';
        $validations['system_user_id'] = 'required';
        $validations['zone_id'] = 'required';
        return $validations;
    }

    public function messages()
    {
        return [
            'logo.required' => 'The Photo field is required',
        ];
    }
}
