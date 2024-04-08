<?php

namespace App\Http\Requests\Community;

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
            'logo' => ['image', 'mimes:jpeg,png,jpg,svg', 'max:2048', 'sometimes', 'nullable'],
            'email' => 'required|email|min:3|max:191|unique:users',
            'company_name' => ['required', 'min:3', 'max:100'],
            'member_from' => ['required', 'date'],
            'expiry' => ['required', 'date'],
            'management_company' => 'required',
            'contact_name' => ['required', 'min:3', 'max:100'],
            'number' => ['required', 'min:8', 'max:15'],
            'position' => ['required', 'min:3', 'max:100'],
            'whatsapp' => ['required', 'min:8', 'max:15'],
            'location' => ['nullable', 'min:3', 'max:255'],
            'lat' => ['nullable'],
            'lon' => ['nullable'],
        ];
    }
}
