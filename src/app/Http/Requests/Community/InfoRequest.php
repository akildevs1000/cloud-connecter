<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class InfoRequest extends FormRequest
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
            'logo' => ['image', 'mimes:jpeg,png,jpg,svg', 'max:2048', 'sometimes', 'nullable'],
            'name' => 'required|min:3|max:20',
            'email' => 'required|email|min:3|max:191|unique:users',
            'member_from' => ['required', 'date'],
            'expiry' => ['required', 'date'],
            'management_company' => 'required',
        ];
    }
}
